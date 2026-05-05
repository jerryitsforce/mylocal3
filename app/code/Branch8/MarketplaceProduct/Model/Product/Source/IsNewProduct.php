<?php

namespace Branch8\MarketplaceProduct\Model\Product\Source;

use Magento\Framework\Data\OptionSourceInterface;

class IsNewProduct implements OptionSourceInterface
{
    public function toOptionArray()
    {
        $options = [
            [
                'label' => 'New',
                'value' => 1
            ],
            [
                'label' => 'Modified Product',
                'value' => 0
            ]
        ];
        return $options;
    }

}