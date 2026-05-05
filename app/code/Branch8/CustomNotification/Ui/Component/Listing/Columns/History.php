<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class History extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['id'])) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->context->getUrl('notibox/notification/history', ['id' => $item['id']]),
                        'label' => __('View'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
