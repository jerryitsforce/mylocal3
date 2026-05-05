<?php
namespace Branch8\RewardSystem\Model\Config\Source;

class Conditions implements \Magento\Framework\Data\OptionSourceInterface
{
    const COND_API = 1;

    const COND_VISIT_URL = 2;

    const COND_FIRST_PURCHASE = 3;

    const COND_LEVEL_CHANGE = 4;

    public function toOptionArray(){
        return [
            ['value' => self::COND_API, 'label' => __('API Call Trigger')],
            ['value' => self::COND_VISIT_URL, 'label' => __('Visit Specific Link Trigger')],
            ['value' => self::COND_FIRST_PURCHASE, 'label' => __('First Purchase Trigger')],
            ['value' => self::COND_LEVEL_CHANGE, 'label' => __('Membership Level Change')]
        ];
    }

    public function getAllOptions(){
        return [
            self::COND_API => __('API Call Trigger'),
            self::COND_VISIT_URL => __('Visit Specific Link Trigger'),
            self::COND_FIRST_PURCHASE => __('First Purchase Trigger'),
            self::COND_LEVEL_CHANGE => __('Membership Level Change')
        ];
    }
}
