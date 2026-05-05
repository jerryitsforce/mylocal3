<?php

namespace HotaiConnected\Report\Model\Report;

class PointOrderRedemptionStatusReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '點數訂單兌點狀態檢查';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT soi.item_id,
                   soi.flow_status,
                   so.increment_id,
                   so.state,
                   so.status,
                   so.rma_status,
                   soi.row_total_point_used,
                   soi.`hotai_point_deduction_point_trans_s_n`,
                   soi.hotai_point_deduction_point_progress_status,
                   so.created_at,
                   soi.ticket_retry_status,
                   soi.ticket_retry_count
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            WHERE soi.row_total_point_used > 0
              AND soi.hotai_point_deduction_point_progress_status != 2
              AND so.status NOT IN ('canceled',
                                    'pending_payment',
                                    'returned')
              AND soi.flow_status NOT IN ('returned')
              AND so.rma_status IS NULL
              AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND so.created_at <= DATE_SUB(NOW(), INTERVAL 4 HOUR)
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
        $itemList = implode(", ", $incrementIds);
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
