<?php

namespace HotaiConnected\Report\Model\Report;

class MissingPointDeductionReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '有用點數但沒有deduction紀錄';
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
                soi.row_total_point_used,
                soi.hotai_point_deduction_point_trans_datetime,
                soi.name,
                soi.sku,
                so.ecpay_invoice_number,
                so.ecpay_invoice_status,
                so.is_paid,
                soi.item_id,
                so.increment_id,
                so.created_at,
                so.state,
                so.status,
                so.rma_status,
                soi.hotai_point_deduction_point_trans_s_n,
                soi.hotai_point_deduction_point_trace_no,
                soi.hotai_point_return_point_trace_no
            FROM
                sales_order so
            LEFT JOIN sales_order_item soi ON
                soi.order_id = so.entity_id
            WHERE
                so.increment_id NOT LIKE '%hotai_order%'
                AND soi.hotai_point_deduction_point_trans_s_n IS NULL
                AND so.state != 'new'
                AND so.status NOT IN ('canceled','cancel_pending_for_po_rc')
                AND soi.row_total_point_used != 0
                AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY so.increment_id DESC
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
