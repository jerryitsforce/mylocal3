<?php

namespace Branch8\CustomerTicketTable\Ui\Source;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    const STATUS_OVER_DUE = -2; // 已過期
    const STATUS_RETURNED = -1; // 已退貨
    const STATUS_IMPORTED = 0;  // 初始匯入
    const STATUS_ALLOCATED = 1; // 預分配(已指定給quote_item)
    const STATUS_UNUSED = 2;    // 未使用;已賣出
    const STATUS_USED = 3;      // 已使用

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
//            ['value' => self::STATUS_OVER_DUE, 'label' => __('已過期')],
            ['value' => self::STATUS_RETURNED, 'label' => __('已退貨')],
            ['value' => self::STATUS_IMPORTED, 'label' => __('初始匯入')],
            ['value' => self::STATUS_ALLOCATED, 'label' => __('預分配')],
            ['value' => self::STATUS_UNUSED, 'label' => __('未使用')],
            ['value' => self::STATUS_USED, 'label' => __('已使用')],
        ];
    }
}
