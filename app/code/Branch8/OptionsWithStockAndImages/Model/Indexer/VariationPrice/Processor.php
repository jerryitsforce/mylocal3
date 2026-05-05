<?php
namespace Branch8\OptionsWithStockAndImages\Model\Indexer\VariationPrice;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

use Branch8\OptionsWithStockAndImages\Helper\CatalogRuleCalculator;


class Processor extends \Magento\Framework\Indexer\AbstractProcessor
{
    const INDEXER_ID = 'branch8_variations_price';

    protected $resource;
    protected $storeManager;
    protected $groupRepository;
    protected $searchCriteriaBuilder;
    protected $ruleCalculator;
    protected $productAction;

    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        CatalogRuleCalculator $ruleCalculator,
        \Magento\Catalog\Model\ResourceModel\Product\Action $productAction
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->ruleCalculator = $ruleCalculator;
        $this->productAction = $productAction;
        parent::__construct($indexerRegistry);
    }

    public function reindexAll()
    {
        $this->doReindex();
    }

    public function reindexList($ids, $forceReindex = false)
    {
        $this->doReindex($ids);
    }

    public function reindexRow($id, $forceReindex = false)
    {
        $this->doReindex([$id]);
    }

    private function doReindex(array $productIds = null)
    {
        $connection = $this->resource->getConnection();
        $indexTable = $this->resource->getTableName('branch8_variations_price_index');
        $variationTable = $this->resource->getTableName('wk_osi_variations');
        $productEntityTable = $this->resource->getTableName('catalog_product_entity');

        if ($productIds) {
            // Resolve row_id to entity_id because wk_osi_variations triggers with row_id
            $selectIds = $connection->select()
                ->from($productEntityTable, ['entity_id'])
                ->where('row_id IN (?)', $productIds);
            $resolvedIds = $connection->fetchCol($selectIds);
            $productIds = array_unique(array_merge($productIds, $resolvedIds));

            $connection->delete($indexTable, ['product_id IN (?)' => $productIds]);
        } else {
            $connection->truncateTable($indexTable);
        }

        $websites = $this->storeManager->getWebsites();
        $customerGroups = $this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        // Prepare basic query to fetch variations
        $select = $connection->select()
            ->from(['v' => $variationTable], ['comb', 'price'])
            ->join(
                ['p' => $productEntityTable],
                'v.product_id = p.row_id',
                ['entity_id']
            );

        if ($productIds) {
            $select->where('p.entity_id IN (?)', $productIds);
        }

        $variations = $connection->fetchAll($select);

        $dataToInsert = [];
        $batchSize = 1000;



        // Track min price per product per website
        $minPrices = [];

        foreach ($websites as $website) {
            $websiteId = (int)$website->getId();
            foreach ($customerGroups as $group) {
                $groupId = (int)$group->getId();
                foreach ($variations as $variation) {
                    $productId = $variation['entity_id'];
                    $variationPrice = (float)$variation['price'];

                    $finalPrice = $this->ruleCalculator->applyRuleOnCustomPrice(
                        (int)$productId,
                        $variationPrice,
                        $websiteId,
                        $groupId
                    );
                    if(!empty($variation['comb'])){
                        $dataToInsert[] = [
                            'variation_comb' => $variation['comb'],
                            'product_id' => $productId,
                            'customer_group_id' => $groupId,
                            'website_id' => $websiteId,
                            'final_price' => $finalPrice,
                        ];
                    }

                    // Track min price across all customer groups to find the absolute lowest price
                    if (!isset($minPrices[$websiteId][$productId])) {
                        $minPrices[$websiteId][$productId] = $finalPrice;
                    } else {
                        $minPrices[$websiteId][$productId] = min($minPrices[$websiteId][$productId], $finalPrice);
                    }

                    if (count($dataToInsert) >= $batchSize) {
                        $connection->insertOnDuplicate($indexTable, $dataToInsert, ['final_price']);
                        $dataToInsert = [];
                    }
                }
            }
        }

        if (!empty($dataToInsert)) {
            $connection->insertOnDuplicate($indexTable, $dataToInsert, ['final_price']);
        }

        // Update min_variation_price attribute
        /*if (!empty($minPrices)) {
            foreach ($minPrices as $websiteId => $productPrices) {
                foreach ($productPrices as $productId => $price) {
                    $this->productAction->updateAttributes(
                        [$productId],
                        ['min_variation_price' => $price],
                        $websiteId
                    );
                }
            }
        }*/

        $this->syncToCatalogIndex($productIds);
    }

    /**
     * Update catalog_product_index_price with min/max values from branch8_variations_price_index
     *
     * @param array|null $ids
     * @return void
     */
    protected function syncToCatalogIndex($ids = null)
    {
        $connection = $this->resource->getConnection();
        $catalogIndexTable = $this->resource->getTableName('catalog_product_index_price');
        $branch8IndexTable = $this->resource->getTableName('branch8_variations_price_index');

        $wherePart = "";
        if (!empty($ids)) {
            // Ensure ids are integers to prevent SQL injection in direct string concat
            $safeIds = array_map('intval', $ids);
            $wherePart = "WHERE product_id IN (" . implode(',', $safeIds) . ")";
        }

        // We update the catalog index table by joining with the aggregated branch8 table
        // We set final_price and min_price to the minimum variation price found
        // We set max_price to the maximum variation price found
        // This data using for storefront price display in live search
        $sql = "
            UPDATE {$catalogIndexTable} c
            INNER JOIN (
                SELECT product_id, customer_group_id, website_id, MIN(final_price) as min_p, MAX(final_price) as max_p
                FROM {$branch8IndexTable}
                {$wherePart}
                GROUP BY product_id, customer_group_id, website_id
            ) b ON c.entity_id = b.product_id
               AND c.customer_group_id = b.customer_group_id
               AND c.website_id = b.website_id
            SET c.final_price = b.min_p,
                c.min_price = b.min_p,
                c.max_price = b.max_p
        ";

        $connection->query($sql);
    }
}
