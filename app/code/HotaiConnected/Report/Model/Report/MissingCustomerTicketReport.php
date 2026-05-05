<?php

namespace HotaiConnected\Report\Model\Report;

class MissingCustomerTicketReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '沒有customer_ticket的相關訂單';
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
                so.created_at,
                soi.item_id,
                so.entity_id,
                soi.hotai_point_deduction_point_memo,
                soi.hotai_point_deduction_point_trans_s_n,
                soi.seller_code,
                so.is_paid,
                so.is_gift_order,
                so.is_gift_confirmed,
                so.recipient_name,
                so.recipient_telephone,
                so.increment_id,
                so.state,
                so.status,
                so.rma_status,
                soi.is_virtual,
                soi.sku,
                soi.name,
                soi.qty_ordered,
                soi.seller_company_name
            FROM
                sales_order so
            LEFT JOIN
                sales_order_item soi ON so.entity_id = soi.order_id
            WHERE
                soi.is_virtual = 1
                AND so.increment_id NOT LIKE '%hotai_order_%'
                AND so.state != 'new'
                AND (
                    (so.is_gift_order = 1 AND so.is_gift_confirmed != 0)
                    OR
                    (so.is_gift_order = 0)
                )
                AND so.status NOT IN ('canceled', 'gift_info_pending', 'cancel_pending_for_po_rc')
                AND so.rma_status IS NULL
                AND NOT EXISTS (
                    SELECT 1
                    FROM customer_ticket ct_sub
                    WHERE ct_sub.sales_order_item_id = soi.item_id
                )
                AND so.created_at <= DATE_SUB(NOW(), INTERVAL 4 HOUR)
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
