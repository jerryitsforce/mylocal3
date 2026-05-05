<?php

namespace HotaiConnected\Report\Model\Report;

class CanceledOrderMissingPointReturnReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '取消訂單,有用點數沒有return紀錄';
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
                so.entity_id,
                so.increment_id,
                so.created_at,
                soi.item_id,
                soi.row_total_point_used,
                so.status,
                soi.hotai_point_deduction_point_memo,
                soi.hotai_point_deduction_point_progress_status,
                soi.hotai_point_deduction_point_trans_s_n,
                soi.hotai_point_deduction_point_trace_no,
                soi.hotai_point_return_point_trace_no,
                soi.hotai_point_deduction_point_commit_or_cancel_fail_counter,
                sales_creditmemo_item.refund_point_status,
                sales_creditmemo_item.description,
                so.state,
                so.STATUS,
                so.rma_status,
                so.ecpay_invoice_status,
                so.ecpay_invoice_number,
                sales_creditmemo.entity_id as creditmemo_id,
                sales_creditmemo_item.entity_id as creditmemo_item_id
            FROM sales_order so
            LEFT JOIN sales_creditmemo ON so.entity_id = sales_creditmemo.order_id
            LEFT JOIN sales_creditmemo_item ON sales_creditmemo.entity_id = sales_creditmemo_item.parent_id
            LEFT JOIN sales_order_item soi ON soi.order_id = so.entity_id
            WHERE so.increment_id NOT LIKE '%hotai_order_%'
                AND so.status = 'canceled'
                AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND soi.row_total_point_used != 0
                AND soi.hotai_point_deduction_point_trans_s_n IS NOT NULL
                AND soi.hotai_point_return_point_trace_no IS NULL
                AND soi.hotai_point_deduction_point_progress_status != 3
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
