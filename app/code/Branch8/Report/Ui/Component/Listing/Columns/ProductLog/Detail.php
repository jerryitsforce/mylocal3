<?php

declare(strict_types=1);

namespace Branch8\Report\Ui\Component\Listing\Columns\ProductLog;

use Branch8\Report\Api\Data\ProductChangeLogInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Detail extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['log_id'])) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->context->getUrl(
                            'report/product_logging/view',
                            ['id' => $item[ProductChangeLogInterface::LOG_ID], 'product_id' => $item['product_id']]
                        ),
                        'label' => __('View'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
