<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

/**
 * Add grid column with salable quantity data
 */
class SalableQuantity extends Column
{
    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        VariationsFactory $variationsFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->variationsFactory = $variationsFactory;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if ($dataSource['data']['totalRecords'] > 0) {
            foreach ($dataSource['data']['items'] as &$row) {
                $row['salable_quantity'] =
                    $this->isSourceItemManagementAllowedForProductType->execute($row['type_id']) === true
                    ? $this->getSalableQuantityItemData($row)
                    : [];
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
    private function getSalableQuantityItemData(array $item): array
    {
        $sku = $item['sku'];
        $sku = htmlspecialchars_decode($sku, ENT_QUOTES | ENT_SUBSTITUTE);
        
        $variationStock = $this->getVariationStock($item);
        if (count($variationStock)) {
            return [
                [
                    'manage_stock' => true,
                    'variation_stock' => $variationStock,
                ]
            ];
        }

        $stockIds = $this->getAssignedStockIdsBySku->execute($sku);
        if (count($stockIds) > 15) {
            return [
                [
                    'manage_stock' => true,
                    'message' => __('Associated to %1 stocks', count($stockIds)),
                ]
            ];
        }

        $salableQuantityData = $this->getSalableQuantityDataBySku->execute($sku);
        return $salableQuantityData;
    }

    private function getVariationStock(array $item)
    {
        $data = [];
        $variations = [];

        if (isset($item['branch8_variations'])) {
            $variations = $item['branch8_variations'];
        } else {
            $row_id = $item['entity_id'] ?? null;
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
                    'stock' => $variation['stock']
                ];
            }
        }
        return $data;
    }
}
