<?php
namespace Branch8\RewardSystem\Model\Config\Source;

class TriggerType implements \Magento\Framework\Data\OptionSourceInterface
{
    const TYPE_AND = 1;

    const TYPE_OR = 2;

    public function toOptionArray(){
        return [
            ['value' => self::TYPE_AND, 'label' => __('Meet any of the conditions')],
            ['value' => self::TYPE_OR, 'label' => __('Meet all conditions')]
        ];
    }
}
