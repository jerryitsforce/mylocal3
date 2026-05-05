<?php

namespace Branch8\Spin2Win\Model\Config\Source;

class SegmentType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const LOSE_TYPE = 0;
    const COUPON_TYPE = 1;
    const VIRTUAL_TYPE = 2;
    const PHYSICAL_TYPE = 3;
    const REWARD_POINT_TYPE = 4;


    public function getAllOptions()
    {
        return [
            [
                'label' => __('Lose'),
                'value' => self::LOSE_TYPE
            ],
            [
                'label' => __('Coupon'),
                'value' => self::COUPON_TYPE
            ],
            [
                'label' => __('Virtual Item'),
                'value' => self::VIRTUAL_TYPE
            ],
            [
                'label' => __('Physical Item'),
                'value' => self::PHYSICAL_TYPE
            ],
            [
                'label' => __('Reward points'),
                'value' => self::REWARD_POINT_TYPE
            ],
        ];

    }

    public function getOptions()
    {
        return [
            self::LOSE_TYPE => __('Lose'),
            self::COUPON_TYPE => __('Coupon'),
            self::VIRTUAL_TYPE => __('Virtual Item'),
            self::PHYSICAL_TYPE => __('Physical Item'),
            self::REWARD_POINT_TYPE => __('Reward points')
        ];
    }
}