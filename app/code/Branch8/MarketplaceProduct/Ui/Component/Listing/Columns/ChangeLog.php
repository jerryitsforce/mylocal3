<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class ChangeLog extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $item[$this->getData('name')]['view'] = [
                'href' => $this->context->getUrl(
                    'marketplacectrl/product/changelog',
                    [
                        'mp_product_id' => $item['entity_id'],
                        'copy' => $this->_data['config']['isCopy'] ?? false
                    ]
                ),
                'label' => __('View'),
                'hidden' => false
            ];
        }

        return $dataSource;
    }
}
