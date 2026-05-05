<?php
namespace Branch8\SplitCart\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            // ['value' => 'systemlog', 'label' => __('System Log(var/log/system.log)')],
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')],
            ['value' => 'debuglog', 'label' => __('Exception Log(var/log/debug.log)')]
        ];
    }
}
