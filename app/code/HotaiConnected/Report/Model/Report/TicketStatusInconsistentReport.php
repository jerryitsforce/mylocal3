<?php

namespace HotaiConnected\Report\Model\Report;

class TicketStatusInconsistentReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '球池票券與customer_ticket狀態不一致';
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
                ct.status as customer_ticket_status,
                tet.status as ticket_event_status,
                tet.event_id,
                tet.entity_id,
                te.seller_id,
                te.ticket_type,
                te.amount,
                tet.serial_number,
                ct.member_seq
            FROM ticket_event_ticket tet
            LEFT JOIN ticket_event te ON te.entity_id = tet.event_id
            LEFT JOIN customer_ticket ct ON ct.ticket_table_name = 'ticket_event_ticket'
                AND ct.ticket_table_record_id = tet.entity_id
            WHERE ct.status != tet.status
            ORDER BY tet.entity_id DESC
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
        // 取得所有不重複的 entity_id
        $entityIds = [];
        foreach ($data as $row) {
            if (!in_array($row['entity_id'], $entityIds)) {
                $entityIds[] = $row['entity_id'];
            }
        }

        // 格式化為列表
        $entityList = implode(",", $entityIds);
        $text = "ticket_event_ticket.entity_id:\n" . $entityList;

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
