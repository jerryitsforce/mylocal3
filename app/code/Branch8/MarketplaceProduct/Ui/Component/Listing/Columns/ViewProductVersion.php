<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class ViewProductVersion extends Column
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
            if (!isset($item['id'])) {
                continue;
            }
            $viewUrlPath = $this->getData('config/viewUrlPath') ?: '#';
            $urlEntityParamName = $this->getData('config/urlEntityParamName') ?: 'id';
            $item[$this->getData('name')] = [
                'view' => [
                    'href' => $this->context->getUrl(
                        $viewUrlPath,
                        [
                            $urlEntityParamName => $item['id']
                        ]
                    ),
                    'label' => __('View')
                ]
            ];
        }

        return $dataSource;
    }
}
