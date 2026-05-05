<?php

namespace HotaiConnected\Report\Model\Report;

class NoPointNoPaymentReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '沒用點數又沒付款資訊的訂單';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT so.entity_id, so.increment_id, soi.item_id, soi.sku, soi.name, eihol.include_tax, eihol.point_used, so.state, so.status, so.rma_status, so.point_used_total, so.ecpay_invoice_number, so.ecpay_invoice_status, so.ecpay_invoice_updated_at, spop.*
            FROM sales_order so
            LEFT JOIN sales_parent_order_children spoc ON spoc.children_id = so.entity_id
            LEFT JOIN sales_order_item soi ON soi.order_id = so.entity_id
            LEFT JOIN sales_parent_order_payment spop ON spoc.parent_id = spop.parent_id
            LEFT JOIN ecpay_invoice_hotai_order_invoice_logs eihol ON eihol.order_id = so.entity_id
            WHERE so.increment_id NOT LIKE '%hotai_order%'
              AND so.point_used_total = 0
              AND spop.txn IS NULL
              AND so.status NOT IN ('canceled', 'cancel_pending_for_po_rc')
              AND (soi.row_total_incl_tax - soi.discount_amount) != 0
              AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY so.created_at DESC
        ";
    }

    /**
     * Format data for Slack
     *
     * @param array $data
     * @return array
     */
    public function formatForSlack(array $data): array
    {
        // 取得所有不重複的 increment_id
        $incrementIds = [];
        foreach ($data as $row) {
            if (!in_array($row['increment_id'], $incrementIds)) {
                $incrementIds[] = $row['increment_id'];
            }
        }

        // 格式化為列表
        $itemList = "'" . implode("', '", $incrementIds) . "'";
        $text = "sales_order.increment_id:\n" . $itemList;

        return [
            'channel' => '#sql-monitor',
            'username' => 'webhookbot',
            'attachments' => [
                [
                    'color' => '#ff0000',
                    'title' => $this->getName(),
                    'text' => $text,
                    'footer' => 'SQL Monitor Bot'
                ]
            ]
        ];
    }
}
