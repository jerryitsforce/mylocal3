<?php
namespace Branch8\SpecialPromo\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')]
        ];
    }
}
