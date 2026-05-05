<?php

namespace Branch8\RestrictedProduct\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'allow_customer_groups.log', 'label' => __('allow_customer_groups.log (var/log/allow_customer_groups.log)')],
            ['value' => 'system.log', 'label' => __('system.log (var/log/system.log)')],
            ['value' => 'exception.log', 'label' => __('exception.log (var/log/exception.log)')]
        ];
    }
}
