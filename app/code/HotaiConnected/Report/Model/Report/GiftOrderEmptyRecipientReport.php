<?php

namespace HotaiConnected\Report\Model\Report;

class GiftOrderEmptyRecipientReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '禮物訂單/票券/收禮人資訊為空';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT soi.flow_status, soi.item_id, so.state, so.status, so.rma_status, so.increment_id, so.customer_id AS '購買人', ce2.phone_number AS '購買人手機', so.recipient_telephone AS '收件人手機', ce.entity_id AS '收件人帳號', so.recipient_name
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            LEFT JOIN customer_entity ce2 ON ce2.entity_id = so.customer_id
            LEFT JOIN customer_entity ce ON ce.phone_number = so.recipient_telephone
            WHERE so.is_gift_order = 1
              AND so.recipient_telephone IS NULL
              AND so.status NOT IN ('canceled', 'gift_info_pending', 'cancel_pending', 'pending_payment')
              AND soi.flow_status NOT IN ('returned')
              AND so.rma_status IS NULL
              AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
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
