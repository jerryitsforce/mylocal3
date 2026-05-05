<?php

namespace HotaiConnected\OpenHub\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ApiMode implements ArrayInterface
{
    const MODE_DEV = 'dev';
    const MODE_PRODUCTION = 'production';

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::MODE_DEV, 'label' => __('Development')],
            ['value' => self::MODE_PRODUCTION, 'label' => __('Production')]
        ];
    }
}