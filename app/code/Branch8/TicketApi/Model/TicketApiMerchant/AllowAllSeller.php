<?php

namespace Branch8\TicketApi\Model\TicketApiMerchant;

use Magento\Framework\Data\OptionSourceInterface;

class AllowAllSeller implements OptionSourceInterface
{
    const OPTION_NOT_ALLOW = 0;  // 不允許
    const OPTION_ALLOW     = 1; // 允許

    public function toOptionArray()
    {
        return [
            ['value' => self::OPTION_NOT_ALLOW, 'label' => __('Not allow')],
            ['value' => self::OPTION_ALLOW, 'label' => __('Allow')],
        ];
    }
}
