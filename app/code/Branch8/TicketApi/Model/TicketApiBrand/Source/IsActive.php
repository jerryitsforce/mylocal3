<?php

namespace Branch8\TicketApi\Model\TicketApiBrand\Source;

use Branch8\TicketApi\Model\TicketApiBrand;

class IsActive extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('Inactive'), 'value' => TicketApiBrand::IS_ACTIVE_FALSE],
            ['label' => __('Active'), 'value' => TicketApiBrand::IS_ACTIVE_TRUE],
        ];
    }

    public static function getBrandNameMap()
    {
        return [
            TicketApiBrand::IS_ACTIVE_FALSE => __('Inactive'),
            TicketApiBrand::IS_ACTIVE_TRUE  => __('Active'),
        ];
    }

    public static function getOptionArray()
    {
        return [
            VirtualProductType::TYPE_YOXI_TICKET             => __('YOXI'),
            VirtualProductType::TYPE_EDENRED_TICKET          => __('Edenred'),
            VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET => __('Family Bonus PIN'),
        ];
    }
}