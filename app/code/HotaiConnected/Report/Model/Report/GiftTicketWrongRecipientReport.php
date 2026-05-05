<?php

namespace HotaiConnected\Report\Model\Report;

class GiftTicketWrongRecipientReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '下單人非收禮人,且票券非收禮人';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT soi.item_id, so.state, so.status, so.rma_status, so.increment_id, so.customer_id AS '購買人', ce2.phone_number AS '購買人手機', so.recipient_telephone AS '收件人手機', ce.entity_id AS '收件人帳號', ct.customer_id AS '票券帳號', so.recipient_name, ct.status, qtr.status, ct.redeemed_at, qtr.used_date, ct.ticket_unique_content
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            LEFT JOIN customer_entity ce2 ON ce2.entity_id = so.customer_id
            LEFT JOIN customer_entity ce ON ce.phone_number = so.recipient_telephone
            LEFT JOIN customer_ticket ct ON ct.sales_order_item_id = soi.item_id
            LEFT JOIN qware_ticket_record qtr ON qtr.qware_sn = ct.ticket_unique_content
            WHERE so.is_gift_order = 1
              AND soi.is_virtual = 1
              AND ce.entity_id != ct.customer_id
              AND ce.entity_id IS NOT NULL
              AND so.rma_status IS NULL
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
