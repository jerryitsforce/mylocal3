<?php

namespace HotaiConnected\Report\Model\Report;

class TicketOrderNonPurePointReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '票券訂單，非純點交易檢查';
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
                   so.increment_id,
                   CONVERT_TZ(so.created_at, '+00:00', '+08:00') AS '訂單日期',
                   so.state AS '子訂單state',
                   so.status AS '子訂單status',
                   so.rma_status AS '子訂單rma_status',
                   soi.flow_status AS '子訂單明細status',
                   soi.rma_status AS '子訂單明細rma_status',
                   eihol.created_at AS '訂單結帳序號異動日',
                   so.hotai_checkout_number AS '訂單結帳序號',
                   CASE
                        WHEN (eihol.include_tax) = 0 AND eihol.point_used != 0 THEN '純點'
                        WHEN (eihol.include_tax) != 0 AND eihol.point_used != 0 THEN '點加金'
                        WHEN (eihol.include_tax) != 0 AND eihol.point_used = 0 THEN '純金'
                   END AS '付款方式',
                   soi.seller_company_name,
                   cpev73.value AS '產品名稱',
                   soi.price_incl_tax AS '商品售價',
                   soi.qty_ordered AS '件數',
                   soi.row_total_point_used AS '點數折抵',
                   soi.sku AS 'Product SKU',
                   ct.ticket_unique_content AS '票券序號',
                   CONVERT_TZ(ct.created_at, '+00:00', '+08:00') AS '歸戶日期',
                   ct.redeemed_at AS '核銷日期',
                   ct.use_end_time AS '核銷到期日'
            FROM sales_order so
            LEFT JOIN sales_order_item soi ON soi.order_id = so.entity_id
            LEFT JOIN customer_ticket ct ON ct.sales_order_item_id = soi.item_id
            LEFT JOIN catalog_product_entity cpe ON cpe.sku = soi.sku
            LEFT JOIN ecpay_invoice_hotai_order_invoice_logs eihol ON eihol.order_Id = so.entity_id
            LEFT JOIN catalog_product_entity_varchar cpev73 ON cpev73.attribute_id = 73 AND cpev73.row_id = cpe.row_id
            WHERE soi.is_virtual = 1
              AND so.increment_id NOT LIKE '%hotai_order%'
              AND eihol.include_tax != 0
              AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
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
