<?php

namespace Branch8\MarketplaceProduct\Plugin\Ui\DataProvider\Product;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

class MarketplaceProductDataProvider
{
    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @param VariationsFactory $variationsFactory
     */
    public function __construct(
        VariationsFactory $variationsFactory
    ) {
        $this->variationsFactory = $variationsFactory;
    }

    /**
     * @param DataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(DataProvider $subject, array $result)
    {
        if ($subject->getName() !== 'marketplace_products_listing_data_source') {
            return $result;
        }

        if (empty($result['items'])) {
            return $result;
        }

        $rowIds = [];
        foreach ($result['items'] as $item) {
            if (isset($item['row_id'])) {
                $rowIds[] = $item['row_id'];
            } elseif (isset($item['mage_pro_row_id'])) {
                $rowIds[] = $item['mage_pro_row_id'];
            }
        }

        if (empty($rowIds)) {
            return $result;
        }

        $variationsData = $this->getVariationsData($rowIds);

        foreach ($result['items'] as &$item) {
            $rowId = $item['row_id'] ?? $item['mage_pro_row_id'] ?? null;
            if ($rowId && isset($variationsData[$rowId])) {
                $item['branch8_variations'] = $variationsData[$rowId];
            } else {
                $item['branch8_variations'] = [];
            }
        }

        return $result;
    }

    /**
     * @param array $rowIds
     * @return array
     */
    private function getVariationsData(array $rowIds)
    {
        $collection = $this->variationsFactory->create()->getCollection();
        $collection->addFieldToFilter('product_id', ['in' => $rowIds]);

        $data = [];
        foreach ($collection as $variation) {
            $productId = $variation->getProductId();
            if (!isset($data[$productId])) {
                $data[$productId] = [];
            }
            $data[$productId][] = $variation->getData();
        }

        return $data;
    }
}
