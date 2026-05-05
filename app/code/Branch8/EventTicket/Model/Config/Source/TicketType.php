<?php

namespace Branch8\EventTicket\Model\Config\Source;
use Branch8\TicketApi\Model\TicketApiBrand\Source\Brand;

class TicketType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @return array|array[]|null
     */
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }
        return [
            ['label' => __('YOXI'), 'value' => Brand::BRAND_CODE_YOXI],
            ['label' => __('Edenred'), 'value' => Brand::BRAND_CODE_EDENRED],
            ['label' => __('Family Bonus PIN'), 'value' => Brand::BRAND_CODE_FAMILY_BONUS_PIN],
            ['label' => __('General Notify Ticket'), 'value' => Brand::BRAND_CODE_GENERAL_NOTIFY],
            ['label' => __('General Non Notify Ticket'), 'value' => Brand::BRAND_CODE_GENERAL_NON_NOTIFY],
        ];
    }
}