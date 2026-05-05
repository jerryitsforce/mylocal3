<?php

namespace Branch8\AppSettings\Model\Config\Source;

/**
 * @api
 * @since 100.0.2
 */
class Environment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'dev', 'label' => __('dev')],
            ['value' => 'uat', 'label' => __('uat')],
            ['value' => 'prod', 'label' => __('prod')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'dev' => __('dev'),
            'uat' => __('uat'),
            'prod' => __('prod')
        ];
    }
}
