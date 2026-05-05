<?php
namespace Branch8\RewardSystem\Model\Config\Source;

class RewardType implements \Magento\Framework\Data\OptionSourceInterface
{
    const TYPE_COUPON = 1;

    const TYPE_POOL = 2;

    public function toOptionArray(){
        return [
            // ['value' => self::TYPE_COUPON, 'label' => __('Cart Price Rule')],
            ['value' => self::TYPE_POOL, 'label' => __('Ticket Pool')]
        ];
    }
}
