<?php

namespace Branch8\FamilyBonusPin\Model\Config\Source;

/**
 * 對應本模組內透過 HotaiCore Common::writeLog 寫出的 log 位置（相對於 var/log）。
 * 選項 value 供 branch8_debug／DebugLog::isEnable('Branch8_FamilyBonusPin', …) 使用。
 */
class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    /** var/log/FamilyBonusPin/Observer/OrderChangeToCanceled/{Y_m_d}.log — OrderChangeToCanceled */
    public const LOG_ORDER_CHANGE_TO_CANCELED = 'family_bonus_pin_order_change_to_canceled';

    /** var/log/TicketSetAllocationObserver/{Y_m_d}.log — TicketSetAllocationObserver::writeAllocationFailLog */
    public const LOG_TICKET_SET_ALLOCATION_OBSERVER = 'family_bonus_pin_ticket_set_allocation_observer';

    /** var/log/FamilyBonusPin/Cron/UpdateTicketQuantityBySaleTime/{Y_m_d}.log */
    public const LOG_CRON_UPDATE_TICKET_QUANTITY_BY_SALE_TIME = 'family_bonus_pin_cron_update_ticket_quantity_by_sale_time';

    /** var/log/FamilyBonusPin/Cron/CheckSafetyQuantity/{Y_m_d}.log（若 logFileName 為空則為當日檔名） */
    public const LOG_CRON_CHECK_SAFETY_QUANTITY = 'family_bonus_pin_cron_check_safety_quantity';

    /** var/log/FamilyBonusPin/Flow/{Y_m_d}.log — Helper\Flow */
    public const LOG_FLOW = 'family_bonus_pin_flow';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_ORDER_CHANGE_TO_CANCELED,
                'label' => __(
                    'Order change canceled (var/log/FamilyBonusPin/Observer/OrderChangeToCanceled/{Y_m_d}.log)'
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
                    'Cron UpdateTicketQuantityBySaleTime (var/log/FamilyBonusPin/Cron/UpdateTicketQuantityBySaleTime/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_CRON_CHECK_SAFETY_QUANTITY,
                'label' => __(
                    'Cron CheckSafetyQuantity (var/log/FamilyBonusPin/Cron/CheckSafetyQuantity/{Y_m_d}.log)'
                ),
            ],
            [
                'value' => self::LOG_FLOW,
                'label' => __('Flow helper (var/log/FamilyBonusPin/Flow/{Y_m_d}.log)'),
            ],
        ];
    }
}
