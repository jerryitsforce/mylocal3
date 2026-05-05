<?php

namespace Branch8\Yoxi\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    const LOG_OPTION_VALUE_CHECK_SAFETY_QUANTITY = "yoxi_check_safety_quantity";
    const LOG_OPTION_VALUE_UPDATE_TICKET_QUANTITY_BY_SALE_TIME = "yoxi_update_ticket_quantity_by_sale_time";
    const LOG_OPTION_VALUE_RESET_TICKET_STATUS_FOR_RETURN_ORDER = "yoxi_reset_ticket_status_for_return_order";
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_OPTION_VALUE_CHECK_SAFETY_QUANTITY,
                'label' => __(self::LOG_OPTION_VALUE_CHECK_SAFETY_QUANTITY . ' (var/log/Yoxi/Cron/CheckSafetyQuantity/Y_m_d.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_UPDATE_TICKET_QUANTITY_BY_SALE_TIME,
                'label' => __(self::LOG_OPTION_VALUE_UPDATE_TICKET_QUANTITY_BY_SALE_TIME . ' (var/log/Yoxi/Cron/UpdateTicketQuantityBySaleTime/Y_m_d.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_RESET_TICKET_STATUS_FOR_RETURN_ORDER,
                'label' => __(self::LOG_OPTION_VALUE_RESET_TICKET_STATUS_FOR_RETURN_ORDER . ' (var/log/Yoxi/Observer/ResetTicketStatusForReturnOrder/Y_m_d.log)'),
            ],
        ];
    }
}
