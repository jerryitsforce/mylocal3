<?php

namespace Branch8\HotaiCore\Model\Ticket;

class TicketRetryStatus
{
    const FIELD_NAME = 'ticket_retry_status';

    const STATUS_RECORD_EXISTS_ERROR = -2; // 異常, 票券紀錄已存在
    const STATUS_RETRY_LIMIT_ERROR   = -1; // 異常, 重新請求已達次數上限
    const STATUS_DEFAULT             = 0; // 初始狀態
    const STATUS_NEED_RETRY          = 1; // 需要重新請求
    const STATUS_RETRY_SUCCESS       = 2; // 重新請求成功
    const STATUS_PENDING             = 3; // 等待中, API 回應 202 狀態
}