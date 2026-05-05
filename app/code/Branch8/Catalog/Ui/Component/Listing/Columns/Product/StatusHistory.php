<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class StatusHistory extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    $params = [
                        'id' => $item['entity_id'],
                        'attribute' => ProductAttributeInterface::CODE_STATUS
                    ];
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->context->getUrl('catalog/product_status/history', $params),
                        'label' => __('View'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
