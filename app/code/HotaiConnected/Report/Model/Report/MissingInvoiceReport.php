<?php

namespace HotaiConnected\Report\Model\Report;

class MissingInvoiceReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '沒有發票主檔紀錄的訂單（排除4小時內建立者）';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT
                so.created_at,
                soi.item_id,
                soi.hotai_point_deduction_point_progress_status,
                soi.hotai_point_deduction_point_trans_s_n,
                so.entity_id,
                so.ecpay_invoice_status,
                so.is_paid,
                so.ecpay_invoice_customer_identifier,
                eihol.status,
                so.point_used_total,
                so.increment_id,
                so.state,
                so.status,
                so.rma_status,
                so.ecpay_invoice_number,
                so.ecpay_invoice_status
            FROM sales_order so
            LEFT JOIN ecpay_invoice_hotai_order_invoice_logs eihol ON eihol.order_id = so.entity_id
            LEFT JOIN sales_order_item soi ON soi.order_id = so.entity_id
            WHERE so.increment_id NOT LIKE '%hotai_order%'
                AND so.ecpay_invoice_status = 0
                AND so.status NOT IN ('canceled', 'pending_payment', 'cancel_pending_for_po_rc', 'parent_order_failed')
                AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND so.created_at <= DATE_SUB(NOW(), INTERVAL 4 HOUR)
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
