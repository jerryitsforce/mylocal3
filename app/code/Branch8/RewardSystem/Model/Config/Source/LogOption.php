<?php
namespace Branch8\RewardSystem\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'ars', 'label' => __('All Reward Log Log(var/log/ars.log)')]
        ];
    }
}
