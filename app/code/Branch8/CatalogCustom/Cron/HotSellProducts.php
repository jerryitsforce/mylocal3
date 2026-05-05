<?php

namespace Branch8\CatalogCustom\Cron;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Config;

class HotSellProducts
{
    private const HOT_SELL_ATTRIBUTE_CODE = 'hot_sell';
    private const BESTSELLER_LIMIT = 5;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Grouped
     */
    private $grouped;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var ProductAction
     */
    private $productAction;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    private $attribute = null;
    private $eavConfig;

    private $processedProducts = [];
    private CacheInterface $cache;
    private \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Grouped $grouped
     * @param Configurable $configurable
     * @param ProductAction $productAction
     * @param LoggerInterface $logger
     * @param Config $eavConfig
     * @param CacheInterface $cache
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     */
    public function __construct(
        ResourceConnection                         $resourceConnection,
        Grouped                                    $grouped,
        Configurable                               $configurable,
        ProductAction                              $productAction,
        LoggerInterface                            $logger,
        Config                                     $eavConfig,
        CacheInterface                             $cache,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->grouped = $grouped;
        $this->configurable = $configurable;
        $this->productAction = $productAction;
        $this->logger = $logger;
        $this->_storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
        $this->cache = $cache;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttribute()
    {
        if ($this->attribute === null) {
            $this->attribute = $this->eavConfig->getAttribute('catalog_product', self::HOT_SELL_ATTRIBUTE_CODE);
        }
        return $this->attribute;
    }

    /**
     * Execute the cron job
     *
     * @return void
     */
    public function execute()
    {
        try {
            $indexList = [
                'catalog_product_attribute'
            ];
            $bestsellers = $this->getBestsellersData();
            if (empty($bestsellers)) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'systemlog')){
                    $this->logger->info('No bestsellers data found.');
                }
                
                return;
            }
            $updateData = $this->processProductRelations($bestsellers);
            $this->updateProductAttributes($updateData);
            if ($this->processedProducts) {
                //$this->cache->clean($this->processedProducts);
                $products = array_keys($this->processedProducts);
                foreach ($indexList as $index) {
                    try {
                        $indexer = $this->indexerRegistry->get($index);
                        //check is indexer is scheduled
                        if (!$indexer->isScheduled()) {
                            $indexer->reindexList($products);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'exceptionlog')){
                $this->logger->critical('Error in HotSellProducts cron: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Fetch bestsellers data from database
     *
     * @return array
     */
    private function getBestsellersData(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('sales_bestsellers_aggregated_daily');
        //
        $select = $connection->select()
            ->from('catalog_product_entity', ['row_id', 'sales_bestsellers_aggregated_daily.product_id', 'total_qty' => 'SUM(qty_ordered)'])
            ->join($tableName, 'catalog_product_entity.entity_id = sales_bestsellers_aggregated_daily.product_id', [])
          //  ->where('rating_pos <= ?', self::BESTSELLER_LIMIT)
            ->where('store_id = ?', 0)
            //->where('product_id = ? ','638495')
            ->group('sales_bestsellers_aggregated_daily.product_id');;
           // echo $select;die;
        return $connection->fetchAll($select);
    }

    /**
     * Process product relations and aggregate quantities
     *
     * @param array $bestsellers
     * @return array
     */
    private function processProductRelations(array $bestsellers): array
    {
        $updateData = [];
        foreach ($bestsellers as $result) {
            try {
                $productId = (int)$result['product_id'];
                $totalQty = (float)$result['total_qty'];
                $rowId = (int)$result['row_id'];
                $qty = isset($updateData[$productId]) ? (int)$updateData[$productId]['value'] : 0;
                // Add simple product quantity
                $updateData[$productId] = $this->buildRowData($rowId, $qty + $totalQty);
                $this->setProcessedProducts([$productId]);
                // Process parent products
                $parentProducts = $this->aggregateParentProductData($productId, $totalQty, $updateData);
                $this->setProcessedProducts($parentProducts);
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'exceptionlog')){
                    $this->logger->error('Error processing product ID ' . $productId . ': ' . $e->getMessage());
                }
            }
        }
        return $updateData;
    }

    /**
     * @param $productIds
     * @return HotSellProducts
     */
    private function setProcessedProducts($productIds): HotSellProducts
    {
        foreach ($productIds as $productId) {
            $this->processedProducts[$productId] = Product::CACHE_TAG . '_' . $productId;
        }
        return $this;
    }

    /**
     * @param int $productId
     * @param float $totalQty
     * @param array $updateData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function aggregateParentProductData(int $productId, float $totalQty, array &$updateData): array
    {
        // Process grouped products
        $parentIdsGroup = $this->grouped->getParentIdsByChild($productId);
        $rows = $this->getRowIds($parentIdsGroup);
        if (count($rows)) {
            foreach ($rows as $rowId => $productId) {
                $qty = isset($updateData[$productId]) ? (int)$updateData[$productId]['value'] : 0;
                $updateData[$productId] = $this->buildRowData($rowId,
                    $qty + $totalQty
                );
            }
        }
        // Process configurable products
        $parentIdsConfig = $this->configurable->getParentIdsByChild($productId);
        $rows = $this->getRowIds($parentIdsConfig);
        if (count($rows)) {
            foreach ($rows as $rowId => $productId)  {
                $qty = isset($updateData[$productId]) ? (int)$updateData[$productId]['value'] : 0;
                $updateData[$productId] = $this->buildRowData($rowId,
                    $qty + $totalQty
                );
            }
        }
        return array_merge($parentIdsGroup, $parentIdsConfig);
    }

    /**
     * @param $rowId
     * @param $totalQty
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function buildRowData($rowId, $totalQty)
    {
        $attribute = $this->getAttribute();
        return [
            'row_id' => $rowId,
            'attribute_id' => $attribute['attribute_id'],
            'value' => $totalQty,
            'store_id' => 0//this attribute is global
        ];
    }

    /**
     * @param array $productIds
     * @return array
     */
    private function getRowIds(array $productIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('catalog_product_entity', ['row_id', 'entity_id'])
            ->where('entity_id in (?)', $productIds);
        return $connection->fetchPairs($select);
    }

    /**
     * Update product attributes using bulk operation
     *
     * @param array $updateData
     */
    private function updateProductAttributes(array $updateData): void
    {
        if (empty($updateData)) {
            return;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $connection->insertOnDuplicate('catalog_product_entity_int', $updateData, ['value']);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'exceptionlog')){
                $this->logger->critical('Error updating product attributes: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}
