<?php

namespace Branch8\GeneralNonNotifyTicket\Model\Config\Source;

/**
 * 對應本模組內透過 HotaiCore Common::writeLog 寫出的 log 位置（相對於 var/log）。
 * 選項 value 供 branch8_debug／DebugLog::isEnable('Branch8_GeneralNonNotifyTicket', …) 使用。
 */
class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    /** var/log/GeneralNonNotifyTicket/Observer/OrderChangeToCanceled/{Y_m_d}.log — OrderChangeToCanceled */
    public const LOG_ORDER_CHANGE_TO_CANCELED = 'general_non_notify_ticket_order_change_to_canceled';

    /** var/log/TicketSetAllocationObserver/{Y_m_d}.log — TicketSetAllocationObserver::writeAllocationFailLog */
    public const LOG_TICKET_SET_ALLOCATION_OBSERVER = 'general_non_notify_ticket_ticket_set_allocation_observer';

    /** var/log/GeneralNonNotifyTicket/Cron/UpdateTicketQuantityBySaleTime/{Y_m_d}.log */
    public const LOG_CRON_UPDATE_TICKET_QUANTITY_BY_SALE_TIME =
        'general_non_notify_ticket_cron_update_ticket_quantity_by_sale_time';

    /** var/log/GeneralNonNotifyTicket/Flow/{Y_m_d}.log — Helper\Flow */
    public const LOG_FLOW = 'general_non_notify_ticket_flow';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_ORDER_CHANGE_TO_CANCELED,
                'label' => __(
                    'Order change canceled (var/log/GeneralNonNotifyTicket/Observer/OrderChangeToCanceled/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_TICKET_SET_ALLOCATION_OBSERVER,
                'label' => __(
                    'Ticket allocation fail (var/log/TicketSetAllocationObserver/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_CRON_UPDATE_TICKET_QUANTITY_BY_SALE_TIME,
                'label' => __(
                    'Cron UpdateTicketQuantityBySaleTime (var/log/GeneralNonNotifyTicket/Cron/UpdateTicketQuantityBySaleTime/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_FLOW,
                'label' => __('Flow helper (var/log/GeneralNonNotifyTicket/Flow/{Y_m_d}.log)'),
            ],
        ];
    }
}
