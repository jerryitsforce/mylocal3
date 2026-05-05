<?php

namespace Branch8\TicketApi\Helper\CheckHandler\Const;

class SerialNoStatus
{
    const NOT_EXIST    = "40400"; // 序號不存在
    const OVER_DUE     = "40000"; // 序號已過期
    const DATE_INVALID = "40000"; // 日期問題
    const UNSOLD       = "40001"; // 尚未配發
    const USED         = "40002"; // 已使用
    const RETURNED     = "40003"; // 已退貨/已取消
    const USEABLE      = "20000"; // 允許使用
}
