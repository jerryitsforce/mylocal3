<?php
namespace Branch8\RewardSystem\Model\Config\Source;

class UserLimit implements \Magento\Framework\Data\OptionSourceInterface
{
    const TYPE_UNLIMIT = 0;

    const TYPE_DAILY = 1;

    const TYPE_EVENT = 2;

    public function toOptionArray(){
        return [
            ['value' => self::TYPE_UNLIMIT, 'label' => __('Unlimited')],
            ['value' => self::TYPE_DAILY, 'label' => __('Daily Usage Limit per User')],
            ['value' => self::TYPE_EVENT, 'label' => __('Total Usage Limit per User')]
        ];
    }
}
