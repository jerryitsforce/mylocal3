<?php
namespace Branch8\Preorder\Model\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'exceptionlog', 'label' => __('IsPreorder Exception Log(var/log/exception.log)')],
            ['value' => 'debuglog', 'label' => __('Cron InStock Log(var/log/debug.log)')]
        ];
    }
}
