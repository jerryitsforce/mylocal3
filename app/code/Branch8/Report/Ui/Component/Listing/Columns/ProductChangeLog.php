<?php

declare(strict_types=1);

namespace Branch8\Report\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class ProductChangeLog extends Column
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
                        'href' => $this->context->getUrl(
                            'report/product_logging/grid',
                            ['id' => $item['entity_id']]
                        ),
                        'label' => __('View'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
