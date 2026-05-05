<?php

namespace Branch8\Spin2Win\Model\Config\Source;

class SpinType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const DAILY_X_TIME = 1;

    const FIXED_X_TIME = 2;

    public function getAllOptions()
    {
        return [
            ['label' => __('Daily X Times'), 'value' => self::DAILY_X_TIME],
            ['label' => __('Fixed X Times'), 'value' => self::FIXED_X_TIME]
        ];
    }
}