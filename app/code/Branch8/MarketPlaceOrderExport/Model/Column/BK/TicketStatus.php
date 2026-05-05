<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\HotaiCore\Model\Ticket\Status;
use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class TicketStatus implements ColumnInterface
{
    private $cached = [];
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getHeader()
    {
        return __('Ticket Status');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['is_virtual'] !== 1) {
            return '';
        }

        return $this->getStatus($row['item_id']);
    }

    /**
     * @return string
     */
    private function getStatus($itemId)
    {
        $table = $this->resourceConnection->getTableName('yoxi_ticket_record_v2');
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from($table, ['status'])->where('sales_order_item_id = ?', $itemId);
        $row = $connection->fetchRow($select);
        if (empty($row)) {
            return '';
        }
        /**
         * app/code/Branch8/HotaiCore/Model/Ticket/Status.php
         * const STATUS_OVER_DUE = -3; // 已過期
         * const STATUS_RETURNED = -2; // 已退貨(已取消)
         * const STATUS_ERROR = -1; // 異常
         * const STATUS_IMPORTED = 0; // 初始匯入
         * const STATUS_ALLOCATED = 1; // 預分配(已指定給quote_item)
         * const STATUS_UNUSED = 2; // 未使用;已賣出
         * const STATUS_USED = 3; // 已使用
         */
        switch ($row['status']) {
            case Status::STATUS_OVER_DUE:
                return __('已過期');
            case Status::STATUS_RETURNED:
                return __('已退貨');
            case Status::STATUS_ERROR:
                return __('異常');
            case Status::STATUS_IMPORTED:
                return __('初始匯入');
            case Status::STATUS_ALLOCATED:
                return __('預分配');
            case Status::STATUS_UNUSED:
                return __('未使用');
            case Status::STATUS_USED:
                return __('已使用');
            default:
                return '';
        }
    }
}
