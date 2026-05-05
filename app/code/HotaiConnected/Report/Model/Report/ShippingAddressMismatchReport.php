<?php

namespace HotaiConnected\Report\Model\Report;

class ShippingAddressMismatchReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '超取宅配地址不一致';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT so.entity_id AS order_id, so.created_at, so.shipping_method, so.increment_id, so.state, so.status, so.rma_status, soa.*
            FROM sales_order_address soa
            LEFT JOIN sales_order so ON so.entity_id = soa.parent_id
            WHERE soa.parent_id IN
                (SELECT entity_id
                 FROM sales_order
                 WHERE shipping_method = 'hotai_711_hotai_711'
                   AND increment_id NOT LIKE '%hotai_order%')
              AND address_type = 'shipping'
              AND (city NOT LIKE '%門市%' AND cvs_store_code is NULL)
              AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)

            UNION ALL

            SELECT so.entity_id AS order_id, so.created_at, so.shipping_method, so.increment_id, so.state, so.status, so.rma_status, soa.*
            FROM sales_order_address soa
            LEFT JOIN sales_order so ON so.entity_id = soa.parent_id
            WHERE soa.parent_id IN
                (SELECT entity_id
                 FROM sales_order
                 WHERE shipping_method = 'hotai_delivery_hotai_delivery'
                   AND increment_id NOT LIKE '%hotai_order%')
              AND address_type = 'shipping'
              AND city LIKE '%門市%'
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
        // 取得所有不重複的 order_id
        $orderIds = [];
        foreach ($data as $row) {
            if (!in_array($row['order_id'], $orderIds)) {
                $orderIds[] = $row['order_id'];
            }
        }

        // 格式化為列表
        $itemList = implode(",", $orderIds);
        $text = "sales_order.entity_id:\n" . $itemList;

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
