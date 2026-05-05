<?php

namespace Branch8\LiveSearchResync\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'system.log', 'label' => __('system.log (var/log/system.log)')],
            ['value' => 'exception.log', 'label' => __('exception.log (var/log/exception.log)')]
        ];
    }
}
