<?php
declare(strict_types=1);

namespace Branch8\OptionsWithStockIndexer\Model\ResourceModel\Indexer;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;
use Zend_Db_Expr;

class Stock extends AbstractDb
{
    private StockResolverInterface $stockResolver;
    private StoreManagerInterface $storeManager;

    private $stockIds = null;
    private IndexNameBuilder $indexNameBuilder;
    private IndexNameResolverInterface $indexnameResolver;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param IndexNameResolverInterface $indexNameResolver
     * @param IndexNameBuilder $indexNameBuilder
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        StoreManagerInterface                             $storeManager,
        StockResolverInterface                            $stockResolver,
        IndexNameResolverInterface                        $indexNameResolver,
        IndexNameBuilder                                  $indexNameBuilder

    )
    {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexnameResolver = $indexNameResolver;
        parent::__construct($context);
        $this->initStockId();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function initStockId()
    {
        if ($this->stockIds === null) {
            foreach ($this->storeManager->getWebsites() as $website) {
                $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
                $this->stockIds[] = $stock->getStockId();
            }
            sort($this->stockIds);
            $this->stockIds = array_unique((array)$this->stockIds);
        }

        return $this->stockIds;
    }

    protected function _construct()
    {
        $this->_init('branch8_catalog_stock_index', 'row_id');
    }

    /**
     * @param array $productIds
     * @return array
     */
    public function retrieveIndexData(array $productIds): array
    {
        $data = [];
        $connection = $this->getConnection();
        if (empty($productIds)) {
            $productConditions = ['e.entity_id IS NOT NULL'];
        } else {
            $productConditions = ['e.entity_id IN (?)', $productIds];
        }
        $productTable = $connection->getTableName('catalog_product_entity');
        $variationTable = $connection->getTableName('wk_osi_variations');
        $originSelect = $connection->select()->from(['e' => $productTable], []);
        if (empty($this->stockIds)) {
            return [];
        }
        foreach ($this->stockIds as $stockId) {
            $select = clone $originSelect;
            $indexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();
            $stockTable = $this->indexnameResolver->resolveName($indexName);
            $select->joinLeft(
                ['stock' => $stockTable],
                'e.sku = stock.sku',
                []
            )->joinLeft(
                ['v' => $variationTable],
                'v.product_id = e.row_id', []
            );
            $columns = [
                'product_id' => 'e.entity_id',
                'stock_id' => new \Zend_Db_Expr($stockId),
                'combo' => new \Zend_Db_Expr('COALESCE(v.comb,v.comb,"NONE")'),
                'sku' => 'e.sku',
                'quantity' => new \Zend_Db_Expr(
                    '(CASE
                                 WHEN v.comb IS NOT NULL THEN v.stock
                                 ELSE stock.quantity
                               END)'
                ),
                'is_salable' => new \Zend_Db_Expr('
                               (CASE
                                 WHEN v.comb IS NOT NULL AND v.stock > 0 THEN 1
                                 WHEN v.comb IS NOT NULL AND v.stock <= 0  THEN 0
                                 ELSE COALESCE(stock.is_salable, stock.is_salable,0)
                               END)'),
            ];
            $select->columns($columns)->where(
                ...$productConditions
            );
            $data = $data + (array)$connection->fetchAll($select);
        }
        return $data;
    }
    /**
     * @return void
     */

}
