<?php

namespace HotaiConnected\Report\Model\Report;

class DuplicateTicketReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '重複票券查詢';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT 'customer_ticket' AS tbl,
                   ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM customer_ticket
            WHERE ticket_table_name != 'ticket_event_ticket'
            GROUP BY ticket_unique_content, sales_order_item_id 
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'ticket_event_ticket' AS tbl,
                   serial_number AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM ticket_event_ticket
            GROUP BY serial_number
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'family_bonus_pin_ticket_record_v2' AS tbl,
                   serial_number AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM family_bonus_pin_ticket_record_v2
            GROUP BY serial_number, sales_order_item_id 
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'edenred_ticket_record' AS tbl,
                   edenred_voucher_no AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM edenred_ticket_record
            GROUP BY edenred_voucher_no, sales_order_item_id 
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'yoxi_ticket_record_v2' AS tbl,
                   serial_number AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM yoxi_ticket_record_v2
            GROUP BY serial_number, sales_order_item_id 
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'general_notify_ticket_record' AS tbl,
                   serial_number AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM general_notify_ticket_record
            GROUP BY serial_number, sales_order_item_id 
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'general_non_notify_ticket_record' AS tbl,
                   serial_number AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM general_non_notify_ticket_record
            GROUP BY serial_number, sales_order_item_id 
            HAVING COUNT(*) > 1

            UNION ALL

            SELECT 'qware_ticket_record' AS tbl,
                   qware_sn AS ticket_unique_content,
                   COUNT(*) AS count_of_records
            FROM qware_ticket_record
            GROUP BY qware_sn, sales_order_item_id 
            HAVING COUNT(*) > 1
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
        // 取得所有不重複的 ticket_unique_content
        $ticketUniqueContents = [];
        foreach ($data as $row) {
            if (!in_array($row['ticket_unique_content'], $ticketUniqueContents)) {
                $ticketUniqueContents[] = $row['ticket_unique_content'];
            }
        }

        // 格式化為列表
        $itemList = implode(",", $ticketUniqueContents);
        $text = "customer_ticket.ticket_unique_content:\n" . $itemList;

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
