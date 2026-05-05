<?php

namespace Branch8\HotaiCore\Model\Ticket;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    // 2024-11-08 "used" ticket stays at "used" status even if they are over due,
    // only "unused" ticket should be set to "over due" status.
    // app/code/Branch8/CustomerTicketTable/Cron/SetRecordsToOverDue.php
    const STATUS_OVER_DUE  = -3; // 已過期
    const STATUS_RETURNED  = -2; // 已退貨(已取消)
    const STATUS_ERROR     = -1; // 異常
    const STATUS_IMPORTED  = 0; // 初始匯入
    const STATUS_ALLOCATED = 1; // 預分配(已指定給quote_item)
    const STATUS_UNUSED    = 2; // 未使用;已賣出
    const STATUS_USED      = 3; // 已使用

    public function toOptionArray()
    {
        return [
//            ['value' => self::STATUS_OVER_DUE, 'label' => __('已過期')],
            ['value' => self::STATUS_RETURNED, 'label' => __('已退貨')],
            ['value' => self::STATUS_ERROR, 'label' => __('異常')],
            ['value' => self::STATUS_IMPORTED, 'label' => __('初始匯入')],
            ['value' => self::STATUS_ALLOCATED, 'label' => __('預分配')],
            ['value' => self::STATUS_UNUSED, 'label' => __('未使用')],
            ['value' => self::STATUS_USED, 'label' => __('已使用')],
        ];
    }
}