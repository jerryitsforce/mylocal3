<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class CustomerView extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->context->getUrl('customer/index/edit', ['id' => $item['customer_id']]),
                        'label' => __('View'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
