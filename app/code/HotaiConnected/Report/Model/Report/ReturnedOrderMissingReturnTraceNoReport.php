<?php

namespace HotaiConnected\Report\Model\Report;

class ReturnedOrderMissingReturnTraceNoReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '訂單returned沒有return單號';
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
                soi.item_id,
                soi.hotai_point_return_point_trace_no,
                so.increment_id,
                so.created_at,
                so.state,
                so.status,
                so.rma_status,
                soi.row_total_point_used,
                soi.rma_status,
                soi.hotai_point_deduction_point_trans_s_n,
                soi.hotai_point_deduction_point_trace_no,
                pt.trans_type,
                pt.trace_no
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            LEFT JOIN hotai_point_api_record pt ON pt.trans_s_n = soi.hotai_point_deduction_point_trans_s_n
            WHERE so.increment_id NOT LIKE '%hotai_order_%'
              AND soi.row_total_point_used != 0
              AND so.rma_status IS NOT NULL
              AND soi.hotai_point_return_point_trace_no IS NULL
              AND soi.flow_status IN ('returned')
              AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY so.entity_id DESC
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
        // 取得所有不重複的 item_id
        $itemIds = [];
        foreach ($data as $row) {
            if (!in_array($row['item_id'], $itemIds)) {
                $itemIds[] = $row['item_id'];
            }
        }

        // 格式化為列表
        $itemList = implode(", ", $itemIds);
        $text = "sales_order_item.item_id:\n" . $itemList;

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
