<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\Ui\Component\Listing\Columns\Column;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Add grid column with salable quantity data
 */
class ReservedQuantity extends Column
{
    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var GetAssignedStockIdsBySku
     */
    private $getAssignedStockIdsBySku;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param GetAssignedStockIdsBySku $getAssignedStockIdsBySku
     * @param int $maximumStocksToShow
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        GetAssignedStockIdsBySku $getAssignedStockIdsBySku,
        VariationsFactory $variationsFactory,
        ResourceConnection $resourceConnection,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->getAssignedStockIdsBySku = $getAssignedStockIdsBySku;
        $this->variationsFactory = $variationsFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if ($dataSource['data']['totalRecords'] > 0) {
            foreach ($dataSource['data']['items'] as &$row) {
                $row['reserved_quantity'] = $this->getReservedQuantityItemData($row);
            }
        }
        unset($row);

        return $dataSource;
    }

    /**
     * Get salable quantity data for product
     *
     * @param array $item
     * @return array
     */
    private function getReservedQuantityItemData(array $item): array
    {
        $sku = $item['sku'];
        $sku = htmlspecialchars_decode($sku, ENT_QUOTES | ENT_SUBSTITUTE);
        
        $variationReserved = $this->getVariationReserved($item);
        if (count($variationReserved)) {
            return [
                'is_variation_stock' => true,
                'variation_reserved' => $variationReserved
            ];
        } else {
            $reservedQty = $this->getReservedQuantityBySku($sku);
            return [
                'is_variation_stock' => false,
                'reserved_stock' => $reservedQty
            ];
        }
    }

    private function getIsVariationStock(array $item)
    {
        if (isset($item['branch8_variations']) && !empty($item['branch8_variations'])) {
            return true;
        }
        
        $row_id = $item['row_id'] ?? $item['mage_pro_row_id'] ?? null;
        if ($row_id) {
            $collection = $this->variationsFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $row_id);
            if ($collection->getSize()) {
                return true;
            }
        }
        return false;
    }

    private function getVariationReserved(array $item)
    {
        $data = [];
        $variations = [];

        if (isset($item['branch8_variations'])) {
            $variations = $item['branch8_variations'];
        } else {
            $row_id = $item['row_id'] ?? $item['mage_pro_row_id'] ?? null;
            if ($row_id) {
                $collection = $this->variationsFactory->create()
                                            ->getCollection()
                                            ->addFieldToFilter("product_id", $row_id);
                foreach ($collection as $variation) {
                    $variations[] = $variation->getData();
                }
            }
        }

        if (!empty($variations)) {
            foreach ($variations as $variation) {
                $data[] = [
                    'comb' => $variation['comb'],
                    'reserved_stock' => $variation['ready_to_ship_qty'] ?? 0
                ];
            }
        }
        return $data;
    }

    private function getReservedQuantityBySku(string $sku)
    {
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
