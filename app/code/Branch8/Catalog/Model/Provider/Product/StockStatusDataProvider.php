<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Model\Provider\Product;

use Branch8\Preorder\Model\Source\PreorderMode;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventoryDataExporter\Model\InventoryHelper;
use Magento\CatalogInventoryDataExporter\Model\Query\CatalogInventoryQuery;
use Magento\CatalogInventoryDataExporter\Model\Query\InventoryData;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Store\Model\Store;
use Webkul\MarketplacePreorder\Helper\Data as MarketplacePreorderHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Provide inventory stock status data depending on current Inventory Management system
 *
 * Temporary Inventory Data supports 2 Inventory implementations
 * - Legacy Inventory
 * - MSI
 *
 * Check done by verification status of InventoryIndexer module
 */
class StockStatusDataProvider
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Provide inventory data when MSI modules enabled
     *
     * @var InventoryData
     */
    private InventoryData $inventoryData;

    /**
     * @var CatalogInventoryQuery
     */
    private CatalogInventoryQuery $catalogInventoryQuery;

    /**
     * @var InventoryHelper
     */
    private InventoryHelper $inventoryHelper;

    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @var MarketplacePreorderHelper
     */
    protected MarketplacePreorderHelper $marketplacePreorderHelper;

    /**
     * @var ProductCollectionFactory
     */
    protected ProductCollectionFactory $productCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timeZoneInterface;

    /**
     * @param ResourceConnection $resourceConnection
     * @param InventoryData $inventoryData
     * @param CatalogInventoryQuery $catalogInventoryQuery
     * @param EavConfig $eavConfig
     * @param MarketplacePreorderHelper $marketplacePreorderHelper
     * @param ProductCollectionFactory $productCollectionFactory
     * @param TimezoneInterface $timeZoneInterface
     * @param InventoryHelper|null $inventoryHelper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        InventoryData $inventoryData,
        CatalogInventoryQuery $catalogInventoryQuery,
        EavConfig $eavConfig,
        MarketplacePreorderHelper $marketplacePreorderHelper,
        ProductCollectionFactory $productCollectionFactory,
        TimezoneInterface $timeZoneInterface,
        ?InventoryHelper $inventoryHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->catalogInventoryQuery = $catalogInventoryQuery;
        $this->inventoryData = $inventoryData;
        $this->eavConfig = $eavConfig;
        $this->marketplacePreorderHelper = $marketplacePreorderHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->inventoryHelper = $inventoryHelper ?? ObjectManager::getInstance()->get(InventoryHelper::class);
    }

    /**
     * @param array $feedItems
     * @return array
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception|\Magento\Framework\Exception\LocalizedException
     */
    public function get(array $feedItems): array
    {
        $output = [];
        $ids = [];
        $attribute = $this->eavConfig->getAttribute('catalog_product', 'index_stock_status');
        $options = $attribute->setStoreId(Store::DEFAULT_STORE_ID)->getSource()->getAllOptions();
        $inStockValue = null;
        $outStockValue = null;
        $preorder = null;
        
        foreach ($options as $option) {
            if ($option['label'] === 'In Stock') {
                $inStockValue = $option['value'];
            } elseif ($option['label'] === 'Out of Stock') {
                $outStockValue = $option['value'];
            } elseif ($option['label'] === 'Preorder') {
                $preorder = $option['value'];
            }
        }

        foreach ($feedItems as $value) {
            $ids[$value['productId']] = $value['productId'];
            
            // Set default value
            $defaultStockValue = $value;
            $defaultStockValue['index_stock_status'] = $outStockValue; // Default Value
            $output[$this->getKey($value)] = $this->format($defaultStockValue, $inStockValue, $outStockValue);
        }

        if (empty($ids)) {
            return $output;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('catalog_product_entity_int');
        
        // Determine the link field: row_id for EE (staging enabled), entity_id for CE
        $entityTable = $connection->getTableName('catalog_product_entity');
        $indexTableColumns = $connection->describeTable($tableName);
        $linkedField = isset($indexTableColumns['row_id']) ? 'row_id' : 'entity_id';

        $select = $connection->select()
            ->from(['e' => $entityTable], ['entity_id'])
            ->join(
                ['at_index_stock_status' => $tableName],
                "e.{$linkedField} = at_index_stock_status.{$linkedField} AND at_index_stock_status.attribute_id = " . $attribute->getAttributeId() . " AND at_index_stock_status.store_id = 0",
                ['value']
            )
            ->where('e.entity_id IN (?)', $ids);

        $fetched = $connection->fetchAll($select);
        
        // Map fetched values to output
        $map = [];
        foreach ($fetched as $row) {
            $map[$row['entity_id']] = $row['value'];
        }

        foreach ($feedItems as $item) {
            $productId = $item['productId'];
            if (isset($map[$productId])) {
                $key = $this->getKey($item);
                $statusValue = $map[$productId];
                
                // Override with actual value from DB
                $output[$key] = [
                    'productId' => $productId,
                    'storeViewCode' => $item['storeViewCode'],
                    'index_stock_status' => $statusValue,
                ];
            }
        }

        return $output;
    }

    /**
     * Format output
     *
     * @param array $row
     * @param $inStockValue
     * @param $outStockValue
     * @return array
     */
    private function format(array $row, $inStockValue, $outStockValue) : array
    {
        return [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'index_stock_status' => $row['index_stock_status'],
        ];
    }

    /**
     * @param array $item
     * @return string
     */
    private function getKey(array $item): string
    {
        return $item['productId'] . '-' . $item['storeViewCode'];
    }
}
