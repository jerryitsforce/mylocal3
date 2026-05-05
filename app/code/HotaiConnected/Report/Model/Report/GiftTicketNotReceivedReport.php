<?php

namespace HotaiConnected\Report\Model\Report;

class GiftTicketNotReceivedReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '禮物票券訂單登入後沒拿到';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT ct.status, soi.item_id, so.state, so.status, so.rma_status, so.increment_id, so.customer_id AS '購買人', ce2.phone_number AS '購買人手機', so.recipient_telephone AS '收件人手機', ce.entity_id AS '收件人帳號', so.recipient_name, ct.customer_id
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            LEFT JOIN customer_entity ce2 ON ce2.entity_id = so.customer_id
            LEFT JOIN customer_entity ce ON ce.phone_number = so.recipient_telephone
            LEFT JOIN customer_ticket ct ON ct.sales_order_item_id = soi.item_id
            WHERE so.is_gift_order = 1
              AND soi.is_virtual = 1
              AND ct.customer_id = 0
              AND so.rma_status != 'rma_completed'
              AND ce.entity_id IS NOT NULL
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
