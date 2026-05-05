<?php

namespace HotaiConnected\Report\Model\Report;

class TicketIssuedButProcessingReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '已給票券但訂單狀態還在processing';
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
                so.entity_id,
                so.increment_id,
                so.state,
                so.status,
                ct.status as ticket_status,
                so.created_at,
                soi.flow_status,
                ct.ticket_table_name,
                ct.record_id
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            LEFT JOIN customer_ticket ct ON ct.sales_order_item_id = soi.item_id
            WHERE ct.status = 2
                AND so.increment_id NOT LIKE '%hotai_order_%'
                AND so.status NOT IN ('complete', 'arrived', 'canceled')
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
