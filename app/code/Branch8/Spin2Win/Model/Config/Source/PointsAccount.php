<?php

namespace Branch8\Spin2Win\Model\Config\Source;

class PointsAccount extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const TYPE_DEFAULT = 0;

    const TYPE_CUSTOMIZE = 1;

    public function getAllOptions()
    {
        return [
            ['label' => __('Default'), 'value' => self::TYPE_DEFAULT],
            ['label' => __('Customize'), 'value' => self::TYPE_CUSTOMIZE]
        ];
    }
}