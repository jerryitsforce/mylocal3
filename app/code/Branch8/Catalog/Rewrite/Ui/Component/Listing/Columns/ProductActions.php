<?php

declare(strict_types=1);

namespace Branch8\Catalog\Rewrite\Ui\Component\Listing\Columns;

class ProductActions extends \Magento\Catalog\Ui\Component\Listing\Columns\ProductActions
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $storeId = $this->context->getFilterParam('store_id');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['edit'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'catalog/product/edit',
                        ['id' => $item['entity_id'], 'store' => $storeId]
                    ),
                    'ariaLabel' => __('Edit ') . ($item['name'] ?? ''),
                    'label' => __('Edit'),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}
