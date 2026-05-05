<?php

namespace Branch8\TicketApi\Model\TicketApiBrand\Source;

use Branch8\HotaiCore\Model\Product\VirtualProductType;

class Brand extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const BRAND_CODE_YOXI               = "yoxi";
    const BRAND_CODE_EDENRED            = "edenred";
    const BRAND_CODE_FAMILY_BONUS_PIN   = "family_bonus_pin";
    const BRAND_CODE_GENERAL_NOTIFY     = "general_notify";
    const BRAND_CODE_GENERAL_NON_NOTIFY = "general_non_notify"; // Add it just for "check" API, not for "use" and "cancel".
    const BRAND_CODE_OPENHUB            = "openhub";

    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('YOXI'), 'value' => VirtualProductType::TYPE_YOXI_TICKET],
            ['label' => __('Edenred'), 'value' => VirtualProductType::TYPE_EDENRED_TICKET],
            ['label' => __('Family Bonus PIN'), 'value' => VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET],
            ['label' => __('General Notify Ticket'), 'value' => VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET],
            ['label' => __('General Non Notify Ticket'), 'value' => VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET],
            ['label' => __('OpenHub'), 'value' => VirtualProductType::TYPE_OPENHUB_TICKET],
        ];
    }

    public static function getBrandNameMap()
    {
        return [
            VirtualProductType::TYPE_YOXI_TICKET               => "YOXI票券",
            VirtualProductType::TYPE_EDENRED_TICKET            => "宜睿票券",
            VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET   => "全家紅利PIN",
            VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET     => "通用型核銷票券",
            VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET => "通用型非核銷票券",
            VirtualProductType::TYPE_OPENHUB_TICKET            => "OpenHub票券",
        ];
    }

    public static function getBrandCodeMap()
    {
        return [
            VirtualProductType::TYPE_YOXI_TICKET               => self::BRAND_CODE_YOXI,
            VirtualProductType::TYPE_EDENRED_TICKET            => self::BRAND_CODE_EDENRED,
            VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET   => self::BRAND_CODE_FAMILY_BONUS_PIN,
            VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET     => self::BRAND_CODE_GENERAL_NOTIFY,
            VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET => self::BRAND_CODE_GENERAL_NON_NOTIFY,
            VirtualProductType::TYPE_OPENHUB_TICKET            => self::BRAND_CODE_OPENHUB,
        ];
    }

    public static function getOptionArray()
    {
        return [
            VirtualProductType::TYPE_YOXI_TICKET               => __('YOXI'),
            VirtualProductType::TYPE_EDENRED_TICKET            => __('Edenred'),
            VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET   => __('Family Bonus PIN'),
            VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET     => __('General Notify Ticket'),
            VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET => __('General Non Notify Ticket'),
            VirtualProductType::TYPE_OPENHUB_TICKET            => __('OpenHub'),
        ];
    }
}