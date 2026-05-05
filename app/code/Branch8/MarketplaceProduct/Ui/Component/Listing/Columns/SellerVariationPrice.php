<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

/**
 * Add grid column with variation price data
 */
class SellerVariationPrice extends Column
{
    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param VariationsFactory $variationsFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        VariationsFactory $variationsFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->variationsFactory = $variationsFactory;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                try {
                    $variantValue = [];
                    $variations = [];

                    // Check for pre-loaded variations
                    if (isset($item['branch8_variations'])) {
                        $variations = $item['branch8_variations'];
                    } else {
                        // Fallback to individual query
                        $rowId = $item['entity_id'] ?? null;
                        if ($rowId) {
                            $collection = $this->variationsFactory->create()
                                ->getCollection()
                                ->addFieldToFilter("product_id", $rowId);
                            foreach ($collection as $variant) {
                                $variations[] = $variant->getData();
                            }
                        }
                    }

                    if (!empty($variations)) {
                        foreach ($variations as $variant) {
                            $price = $variant['price'] ?? '';
                            // Format: comb : price
                            $variantValue[] = sprintf(
                                '<span><strong style="white-space: nowrap;">%s</strong> : <span>%s</span></span>',
                                $variant['comb'],
                                $price
                            );
                        }
                        if (!empty($variantValue)) {
                            $item[$fieldName] = '<div style="width: 100px;">'.implode('<br>', $variantValue).'</div>';
                        } else {
                            $item[$fieldName] = '';
                        }
                    } else {
                        $item[$fieldName] = '';
                    }
                } catch (\Exception $e) {
                    $item[$fieldName] = '';
                }
            }
        }

        return $dataSource;
    }
}
