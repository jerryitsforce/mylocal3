<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Status extends Column
{
    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$fieldName])) {
                continue;
            }
            if (isset($item['changed_field']) && $item['changed_field'] === ProductAttributeInterface::CODE_STATUS) {
                if ((int)$item[$fieldName] === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    $item[$fieldName] = __('Enabled');
                } else {
                    $item[$fieldName] = __('Disabled');
                }
            }
        }

        return $dataSource;
    }
}
