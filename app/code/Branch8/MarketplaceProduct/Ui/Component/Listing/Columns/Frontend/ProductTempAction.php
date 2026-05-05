<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns\Frontend;

use Magento\Ui\Component\Listing\Columns\Column;

class ProductTempAction extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item['product_id']) && $item['product_id'] > 0) {
                $productId = $item['product_id'];
                $url = $this->context->getUrl('marketplace/product/edit', ['id' => $productId]);
                $item[$fieldName . '_html'] =
                    '<div class="wk-row-action-icons"><span class="wk-action-wrapper"><span title="' .
                    __('Edit') . '" class="temp-edit" data-url="' . $url . '"></span></span></div>';
            } elseif (isset($item['temp_id']) && $item['temp_id'] > 0) {
                $tempId = $item['temp_id'];
                $url = $this->context->getUrl('marketplace/product/edit', ['temp_id' => $tempId]);
                $item[$fieldName . '_html'] =
                    '<div class="wk-row-action-icons"><span class="wk-action-wrapper"><span title="' .
                    __('Edit') . '" class="temp-edit" data-url="' . $url . '"></span></span></div>';
            }
        }

        return $dataSource;
    }
}
