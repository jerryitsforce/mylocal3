<?php

namespace HotaiConnected\Report\Model\Report;

class IncompleteTicketOrderReport extends AbstractReport
{
    public function getName(): string
    {
        return '票券未完成訂單報表';
    }

    protected function getSql(): string
    {
        return "
            SELECT
                so.increment_id
            FROM
                sales_order_item soi
            LEFT JOIN
                sales_order so ON soi.order_id = so.entity_id
            LEFT JOIN
                customer_ticket ct ON ct.sales_order_item_id = soi.item_id
            WHERE
                ct.ticket_table_name IN (SELECT DISTINCT(ticket_table_name) FROM customer_ticket)
                AND so.status NOT IN ('complete', 'canceled', 'pending_complete')
                AND NOT EXISTS (
                    SELECT 1
                    FROM
                        sales_order_item soi2
                    LEFT JOIN
                        sales_order so2 ON soi2.order_id = so2.entity_id
                    LEFT JOIN
                        customer_ticket ct2 ON ct2.sales_order_item_id = soi2.item_id
                    WHERE
                        so2.increment_id = so.increment_id
                        AND ct2.ticket_table_name IN (SELECT DISTINCT(ticket_table_name) FROM customer_ticket)
                        AND so2.status NOT IN ('complete', 'canceled', 'pending_complete')
                        AND ct2.status = 2 
                )
            ORDER BY
                so.entity_id DESC
        ";
    }

    public function formatForSlack(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $incrementIds = array_column($data, 'increment_id');
        $text = "偵測到票券未完成的訂單：\n" . implode(', ', array_unique($incrementIds));

        return [
            'channel' => '#sql-monitor',
            'username' => 'Ticket Monitor Bot',
            'attachments' => [
                [
                    'color' => '#FF0000',
                    'title' => $this->getName(),
                    'text' => $text,
                    'footer' => 'SQL Monitor Bot'
                ]
            ]
        ];
    }
}
