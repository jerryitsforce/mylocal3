<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Ui\Component\Listing\Columns\Column;

class UpdatedInBulk extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['history_id']) && !empty($item['updated_in_bulk'])) {
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->context->getUrl(
                            'catalog/product_status/bulkview',
                            ['id' => $item['history_id'], 'product_id' => $item['product_id']]
                        ),
                        'label' => __('View'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
