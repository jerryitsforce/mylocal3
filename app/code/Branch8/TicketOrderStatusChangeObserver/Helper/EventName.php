<?php

namespace Branch8\TicketOrderStatusChangeObserver\Helper;

class EventName
{
    const CHECK_TICKET_ORDER_FOR_USE_API_HANDLE        = "check_ticket_order_for_use_api_handle";
    const CHECK_TICKET_ORDER_FOR_CANCEL_API_HANDLE     = "check_ticket_order_for_cancel_api_handle";
    const CHECK_TICKET_ORDER_FOR_OVER_DUE_CRON         = "check_ticket_order_for_over_due_cron";
    const CHECK_TICKET_ORDER_ALL_COMPLETE_MANUALLY     = "check_ticket_order_all_complete_manually";
    const CHECK_TICKET_ORDER_NOT_ALL_COMPLETE_MANUALLY = "check_ticket_order_not_all_complete_manually";
}
