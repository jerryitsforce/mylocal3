<?php
namespace JustBetter\ProductGridExport\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Branch8\OptionsWithStockAndImages\Helper\Salable;

class InventoryImport
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WriteInterface
     */
    protected $directory;

    /**
     * @var Salable
     */
    protected $salable;

    /**
     * InventoryImport constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param WriteInterface $directory
     * @param Salable $salable
     */
    public function __construct(
        Filesystem $filesystem,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        Salable $salable
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->salable = $salable;
    }

    /**
     * Import inventory data from CSV file with the same format as export
     *
     * @param string $filePath
     * @return array
     */
    public function importInventoryFromCsv($filePath)
    {
        $result = [
            'success' => [],
            'error' => []
        ];

        // Open file
        $stream = $this->directory->openFile($filePath, 'r');
        $stream->lock();

        // Read UTF-8 BOM if present
        $bom = $stream->read(3);
        if ($bom !== "\xEF\xBB\xBF") {
            $stream->seek(0);
        }

        // Read headers
        $headers = $stream->readCsv();
        $headerMap = array_flip($headers);

        // Required columns
        $requiredColumns = [
            '主sku', '單規/多規', '前台可銷售庫存'
        ];
        foreach ($requiredColumns as $col) {
            if (!isset($headerMap[$col])) {
                $result['error'][] = "Missing required column: $col";
                $stream->unlock();
                $stream->close();
                return $result;
            }
        }

        $connection = $this->resourceConnection->getConnection();

        // Process each row
        $variationSkusUpdated = [];
        $reindexSkus = [];
        while (($row = $stream->readCsv()) !== false) {
            if (empty($row) || empty($row[$headerMap['主sku']])) {
                continue;
            }
            $sku = $row[$headerMap['主sku']];
            $qty = $row[$headerMap['前台可銷售庫存']];
            if (!is_numeric($qty)) {
                $result['error'][] = "Invalid quantity for SKU $sku: $qty";
                $this->logger->error('[InventoryImport] Invalid quantity for SKU ' . $sku . ': ' . $qty);
                continue;
            }
            $productType = $row[$headerMap['單規/多規']] ?? '';
            try {
                if($productType === '單規') {
                    $qty = (float)$qty;
                    // Handle single specification product
                    $reservedQty = $this->getReservedQuantityBySku($sku);
                    $qty += $reservedQty; // Adjust quantity by reserved stock
                    if ($qty < 0) {
                        $qty = 0; // Ensure quantity is not negative
                    }
                    $productId = $this->getProductIdBySku($sku);
                    $stockItemTable = $this->resourceConnection->getTableName('cataloginventory_stock_item');
                    $connection->update(
                        $stockItemTable,
                        ['qty' => $qty, 'is_in_stock' => $qty > 0 ? 1 : 0],
                        ['product_id = ?' => $productId]
                    );
                    $stockStatusTable = $this->resourceConnection->getTableName('cataloginventory_stock_status');
                    $connection->update(
                        $stockStatusTable,
                        ['qty' => $qty, 'stock_status' => $qty > 0 ? 1 : 0],
                        ['product_id = ?' => $productId]
                    );
                    $sourceItemTable = $this->resourceConnection->getTableName('inventory_source_item');
                    $connection->update(
                        $sourceItemTable,
                        ['quantity' => $qty, 'status' => $qty > 0 ? 1 : 0],
                        ['sku = ?' => $sku]
                    );
                    $reindexSkus[] = $sku;
                    $result['success'][] = $sku;
                } elseif($productType === '多規') {
                    // Handle multi specification product
                    $variationSkusUpdated[] = $sku;
                    if (isset($headerMap['spec_name']) && !empty($row[$headerMap['spec_name']])) {
                        $variationComb = $row[$headerMap['spec_name']];
                        $rowId = $this->getProductRowId($sku);
                        $variationTable = $this->resourceConnection->getTableName('wk_osi_variations');
                        $data = [
                            'stock' => $qty
                        ];
                        $connection->update(
                            $variationTable,
                            $data,
                            [
                                'comb = ?' => $variationComb,
                                'product_id = ?' => $rowId
                            ]
                        );
                    }

                    $result['success'][] = $sku;
                }
            } catch (\Exception $e) {
                $result['error'][] = $sku . ': ' . $e->getMessage();
                $this->logger->error('[InventoryImport] Error for SKU ' . $sku . ': ' . $e->getMessage());
            }
        }
        $variationSkusUpdated = array_unique($variationSkusUpdated);
        if(count($variationSkusUpdated) > 0){
            $this->syncNeedToRefillStock($variationSkusUpdated);
        }
        $reindexSkus = array_unique($reindexSkus);
        if(count($reindexSkus) > 0){
            $this->reindexProductInventory($reindexSkus);
        }

        $stream->unlock();
        $stream->close();

        return $result;
    }

    protected function getProductAttributeId($attributeCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('eav_attribute'), ['attribute_id'])
            ->where('entity_type_id = ?', 4) // 4 is usually product entity_type_id
            ->where('attribute_code = ?', $attributeCode)
            ->limit(1);
        return $connection->fetchOne($select);
    }

    protected function getProductIdBySku($sku){
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id'])
            ->where('sku = ?', $sku);
        $productId = $connection->fetchOne($select);
        return $productId;
    }

    protected function getProductRowId($sku){
        $currentTime = time();
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['row_id'])
            ->where('sku = ?', $sku)
            ->where('created_in <= ?', $currentTime)
            ->where('updated_in > ?', $currentTime);
        $rowId = $connection->fetchOne($select);
        return $rowId;
    }

    protected function getCostAttribute(){
        $costAttributeId = $this->getProductAttributeId('cost');
        $costSettingAttributeId = $this->getProductAttributeId('cost_setting');
        $costCommissionPercentAttributeId = $this->getProductAttributeId('commission_percent');
        $decimalTableName = $this->resourceConnection->getTableName('catalog_product_entity_decimal');
        $intTableName = $this->resourceConnection->getTableName('catalog_product_entity_int');
        return [$costAttributeId, $costSettingAttributeId, $costCommissionPercentAttributeId, $decimalTableName, $intTableName];
    }

    protected function updateProductCostValue($rowId, $finalPrice){
        $connection = $this->resourceConnection->getConnection();
        // Get current cost
        list($cost, $commission_percent, $cost_setting) = $this->getProductCostValue($rowId);
        // Get new cost for new price
        list($cost, $commission_percent) = $this->salable->calculateProductCost($finalPrice, $cost_setting, $commission_percent, $cost);
        list($costAttributeId, $costSettingAttributeId, $costCommissionPercentAttributeId, $decimalTableName, $intTableName) = $this->getCostAttribute();
        
        if($cost > 0){
            $connection->update(
                $decimalTableName,
                ['value' => $cost],
                ['row_id = ?' => $rowId, 'attribute_id = ?' => $costAttributeId, 'store_id = ?' => 0]
            );
        }
        if($commission_percent > 0){
            $connection->update(
                $decimalTableName,
                ['value' => $commission_percent],
                ['row_id = ?' => $rowId, 'attribute_id = ?' => $costCommissionPercentAttributeId, 'store_id = ?' => 0]
            );
        }
    }

    protected function getProductCostValue($rowId){
        $cost = $commission_percent = $cost_setting = 0;
        if ($rowId) {
            $connection = $this->resourceConnection->getConnection();
            list($costAttributeId, $costSettingAttributeId, $costCommissionPercentAttributeId, $decimalTableName, $intTableName) = $this->getCostAttribute();

            $costSelect = $connection->select()
                ->from($decimalTableName)
                ->where('row_id = ?', $rowId)
                ->where('attribute_id = ?', $costAttributeId)
                ->where('store_id = ?', 0); // Default store
            $costData = $connection->fetchRow($costSelect);
            if($costData){
                $cost = $costData['value'];
            }

            $costSettingSelect = $connection->select()
                ->from($intTableName)
                ->where('row_id = ?', $rowId)
                ->where('attribute_id = ?', $costSettingAttributeId)
                ->where('store_id = ?', 0); // Default store
            $cost_settingData = $connection->fetchRow($costSettingSelect);
            if($cost_settingData){
                $cost_setting = $cost_settingData['value'];
            }

            $costCommissionPercentSelect = $connection->select()
                ->from($decimalTableName)
                ->where('row_id = ?', $rowId)
                ->where('attribute_id = ?', $costCommissionPercentAttributeId)
                ->where('store_id = ?', 0); // Default store
            $commission_percentData = $connection->fetchRow($costCommissionPercentSelect);
            if($commission_percentData){
                $commission_percent = $commission_percentData['value'];
            }

            return [$cost, $commission_percent, $cost_setting];
        }
        
        return [$cost, $commission_percent, $cost_setting];
    }

    protected function updateSingleProductPrice($sku, $newPrice, $newSpecialPrice)
    {
        // Get price attribute ID (usually 75 for price, verify in eav_attribute table)
        $priceAttributeId = $this->getProductAttributeId('price');
        $specialPriceAttributeId = $this->getProductAttributeId('special_price');
        
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_product_entity_decimal');
        $rowId = $this->getProductRowId($sku);
        $finalPrice = $newPrice;
        if ($rowId) {
            $selectPrice = $connection->select()
                ->from($tableName)
                ->where('row_id = ?', $rowId)
                ->where('attribute_id = ?', $priceAttributeId)
                ->where('store_id = ?', 0); // Default store
            $existingPrice = $connection->fetchRow($selectPrice);

            if ($existingPrice) {
                // Update existing price
                $connection->update(
                    $tableName,
                    ['value' => $newPrice],
                    ['row_id = ?' => $rowId, 'attribute_id = ?' => $priceAttributeId, 'store_id = ?' => 0]
                );
            } else {
                // Insert new price
                $connection->insert(
                    $tableName,
                    [
                        'attribute_id' => $priceAttributeId,
                        'store_id' => 0,
                        'row_id' => $rowId,
                        'value' => $newPrice
                    ]
                );
            }

            if($newSpecialPrice > 0 && $newPrice > $newSpecialPrice){
                $finalPrice = $newSpecialPrice;
                $selectSpecialPrice = $connection->select()
                    ->from($tableName)
                    ->where('row_id = ?', $rowId)
                    ->where('attribute_id = ?', $specialPriceAttributeId)
                    ->where('store_id = ?', 0); // Default store
                $existingSpecialPrice = $connection->fetchRow($selectSpecialPrice);
                if ($existingSpecialPrice) {
                    // Update existing price
                    $connection->update(
                        $tableName,
                        ['value' => $newSpecialPrice],
                        ['row_id = ?' => $rowId, 'attribute_id = ?' => $specialPriceAttributeId, 'store_id = ?' => 0]
                    );
                } else {
                    // Insert new price
                    $connection->insert(
                        $tableName,
                        [
                            'attribute_id' => $specialPriceAttributeId,
                            'store_id' => 0,
                            'row_id' => $rowId,
                            'value' => $newSpecialPrice
                        ]
                    );
                }
            }

            // Update cost after update product price
            $this->updateProductCostValue($rowId, $finalPrice);

        }
    }

    protected function syncNeedToRefillStock($skus)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productIds = [];
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('catalog_product_entity');
        
        $select = $connection->select()
            ->from(['p' => $tableName], ['entity_id'])
            ->where('p.sku IN (?)', $skus);

        $productIds = $connection->fetchCol($select);

        if ($productIds) {
            $this->salable->syncNeedToRefill($productIds);
            $this->salable->syncLivesearchInstockByProductIds($productIds);
        }
    }

    protected function reindexProductInventory($skus)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productIds = [];
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('catalog_product_entity');
        
        $select = $connection->select()
            ->from(['p' => $tableName], ['entity_id'])
            ->where('p.sku IN (?)', $skus);

        $productIds = $connection->fetchCol($select);

        if ($productIds) {
            $indexer = $objectManager->get(\Magento\Indexer\Model\Indexer::class);
            $indexer->load('cataloginventory_stock');
            $indexer->reindexList($productIds);
        }
        $this->salable->reindexSourceItems($skus);
    }

    protected function getReservedQuantityBySku($sku){
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_reservation');

        $select = $connection->select()
            ->from(['ir' => $tableName], [])
            ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(ir.quantity)')]) // Sum the quantity
            ->where('ir.sku = ?', $sku)
            ->group('ir.sku');

        $reservedQty = $connection->fetchOne($select);

        return $reservedQty !== false ? (abs((int)$reservedQty)) : 0;
    }
}