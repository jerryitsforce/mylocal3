<?php
namespace Branch8\SellerContactInformation\Model\Config\Source;

class CommissionSource extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const MANULLY_INPUT = 1;

    const ACTIVE_PERIOD = 2;

    const DEFAULT_SETTING = 3;

    public function getAllOptions()
    {
        return [
            ['label' => __('Manually Input'), 'value' => self::MANULLY_INPUT],
            ['label' => __('Active period'), 'value' => self::ACTIVE_PERIOD],
            ['label' => __('Default Setting'), 'value' => self::DEFAULT_SETTING]
        ];
    }

    public function toOptionArray()
    {
        return[
            self::MANULLY_INPUT => __('Manually Input'),
            self::ACTIVE_PERIOD => __('Active period'),
            self::DEFAULT_SETTING => __('Default Setting')
        ];
    }
}
