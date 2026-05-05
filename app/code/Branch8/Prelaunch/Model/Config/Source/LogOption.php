<?php
namespace Branch8\Prelaunch\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'customlog', 'label' => __('Set Product Qty Log(var/log/custom.log)')],
            ['value' => 'old_system_stock', 'label' => __('Get Old System Stock(var/log/os_api.log)')],
        ];
    }
}
