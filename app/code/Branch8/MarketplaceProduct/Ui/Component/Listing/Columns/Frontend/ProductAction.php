<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns\Frontend;

use Magento\Ui\Component\Listing\Columns\Column;

class ProductAction extends Column
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
            if (isset($item['entity_id'])) {
                $productId = $item['entity_id'];
                $url = $this->context->getUrl('marketplace/product/edit/', ['id' => $productId]);
                $urlDelDraft = $this->context->getUrl('marketplace/product/edit/', ['id' => $productId, 'del_draft' => 1]);
                $item[$fieldName . '_html'] =
                    '<div class="wk-row-action-icons"><span class="wk-action-wrapper"><span title="' .
                    __('Edit') . '" class="mp-edit" data-url="' . $url . '" data-url-draft="' . $urlDelDraft . '"' .
                    ' data-product-id="' . $productId . '"' . '></span></span></div>';
            }
        }

        return $dataSource;
    }
}
