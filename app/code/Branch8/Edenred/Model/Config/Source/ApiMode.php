<?php

namespace Branch8\Edenred\Model\Config\Source;

class ApiMode implements \Magento\Framework\Option\ArrayInterface
{
    const API_MODE_DEFAULT    = "dev";
    const API_MODE_DEV        = "dev";
    const API_MODE_PRODUCTION = "production";

    public function toOptionArray()
    {
        return [
            [
                'value' => self::API_MODE_DEV,
                'label' => __('Dev')
            ],
            [
                'value' => self::API_MODE_PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
