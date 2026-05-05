<?php

namespace Branch8\Edenred\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    const LOG_OPTION_VALUE_EDENRED_API              = "edenred_api";
    const LOG_OPTION_VALUE_REQUEST_API_FOR_TICKET   = "edenred_request_api_for_ticket";
    const LOG_OPTION_VALUE_TICKET_REQUEST_RETRY     = "edenred_ticket_request_retry";
    const LOG_OPTION_VALUE_ORDER_CHANGE_TO_CANCELED = "edenred_order_change_to_canceled";
    const LOG_OPTION_VALUE_RECEIVE_NOTIFICATION     = "edenred_receive_notification";
    const LOG_OPTION_VALUE_CHECKOUT_QTY_VALIDATOR   = "edenred_checkout_qty_validator";

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_OPTION_VALUE_EDENRED_API,
                'label' => __(self::LOG_OPTION_VALUE_EDENRED_API . ' (var/log/Edenred/Api/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_REQUEST_API_FOR_TICKET,
                'label' => __(self::LOG_OPTION_VALUE_REQUEST_API_FOR_TICKET . ' (var/log/Edenred/Observer/RequestEdenredApiForTicket/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_TICKET_REQUEST_RETRY,
                'label' => __(self::LOG_OPTION_VALUE_TICKET_REQUEST_RETRY . ' (var/log/Edenred/Cron/TicketRequestRetry/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_ORDER_CHANGE_TO_CANCELED,
                'label' => __(self::LOG_OPTION_VALUE_ORDER_CHANGE_TO_CANCELED . ' (var/log/Edenred/Observer/OrderChangeToCanceled/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_RECEIVE_NOTIFICATION,
                'label' => __(self::LOG_OPTION_VALUE_RECEIVE_NOTIFICATION . ' (var/log/Edenred/Api/ReceiveNotification/{Y_m_d}.log)'),
            ],
            [
                'value' => self::LOG_OPTION_VALUE_CHECKOUT_QTY_VALIDATOR,
                'label' => __(self::LOG_OPTION_VALUE_CHECKOUT_QTY_VALIDATOR . ' (var/log/Checkout/Observer/before_handle_master_quote/{Y_m_d}.log)'),
            ],
        ];
    }
}
