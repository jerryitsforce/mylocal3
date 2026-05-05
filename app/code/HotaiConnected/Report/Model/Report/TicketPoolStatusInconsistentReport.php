<?php

namespace HotaiConnected\Report\Model\Report;

class TicketPoolStatusInconsistentReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '票券狀態不一致';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT 'general_notify_ticket_record' AS tbl, q.is_active AS '有效購物車', fb.quote_item_id, sales_order_item.item_id, fb.serial_number, '票券狀態不一致', CONCAT(
                    REPEAT('*', 10),
                    SUBSTR(fb.serial_number, 11)
                ) AS masked_serial_number, fb.status AS fb_status, ct.status AS ct_status,
                sales_order.is_paid,
                sales_order.status,
                sales_order.is_gift_order,
                sales_order.is_gift_confirmed
            FROM general_notify_ticket_record fb
            LEFT JOIN customer_ticket ct
                ON fb.serial_number = ct.ticket_unique_content AND fb.sales_order_item_id = ct.sales_order_item_id 
            LEFT JOIN quote_item qi ON qi.item_id = fb.quote_item_id
            LEFT JOIN quote q ON q.entity_id = qi.quote_id
            LEFT JOIN sales_order ON sales_order.quote_id = q.entity_id
            LEFT JOIN sales_order_item ON sales_order_item.quote_item_id = qi.item_id
            WHERE fb.status != 0
              AND (ct.status IS NULL OR fb.status <> ct.status)
              AND sales_order.status != 'gift_info_pending'

            UNION ALL

            SELECT 'general_non_notify_ticket_record' AS tbl, q.is_active AS '有效購物車', fb.quote_item_id, sales_order_item.item_id, fb.serial_number, '票券狀態不一致', CONCAT(
                    REPEAT('*', 10),
                    SUBSTR(fb.serial_number, 11)
                ) AS masked_serial_number, fb.status AS fb_status, ct.status AS ct_status,
                sales_order.is_paid,
                sales_order.status,
                sales_order.is_gift_order,
                sales_order.is_gift_confirmed
            FROM general_non_notify_ticket_record fb
            LEFT JOIN customer_ticket ct
                ON fb.serial_number = ct.ticket_unique_content AND fb.sales_order_item_id = ct.sales_order_item_id 
            LEFT JOIN quote_item qi ON qi.item_id = fb.quote_item_id
            LEFT JOIN quote q ON q.entity_id = qi.quote_id
            LEFT JOIN sales_order ON sales_order.quote_id = q.entity_id
            LEFT JOIN sales_order_item ON sales_order_item.quote_item_id = qi.item_id
            WHERE fb.status != 0
              AND (ct.status IS NULL OR fb.status <> ct.status)
              AND sales_order.status != 'gift_info_pending'

            UNION ALL

            SELECT 'yoxi_ticket_record_v2' AS tbl, q.is_active AS '有效購物車', fb.quote_item_id, sales_order_item.item_id, fb.serial_number, '票券狀態不一致', CONCAT(
                    REPEAT('*', 10),
                    SUBSTR(fb.serial_number, 11)
                ) AS masked_serial_number, fb.status AS fb_status, ct.status AS ct_status,
                sales_order.is_paid,
                sales_order.status,
                sales_order.is_gift_order,
                sales_order.is_gift_confirmed
            FROM yoxi_ticket_record_v2 fb
            LEFT JOIN customer_ticket ct
                ON fb.serial_number = ct.ticket_unique_content AND fb.sales_order_item_id = ct.sales_order_item_id 
            LEFT JOIN quote_item qi ON qi.item_id = fb.quote_item_id
            LEFT JOIN quote q ON q.entity_id = qi.quote_id
            LEFT JOIN sales_order ON sales_order.quote_id = q.entity_id
            LEFT JOIN sales_order_item ON sales_order_item.quote_item_id = qi.item_id
            WHERE fb.status != 0
              AND (ct.status IS NULL OR fb.status <> ct.status)
              AND sales_order.status != 'gift_info_pending'

            UNION ALL

            SELECT 'family_bonus_pin_ticket_record_v2' AS tbl, q.is_active AS '有效購物車', fb.quote_item_id, sales_order_item.item_id, fb.serial_number, '票券狀態不一致', CONCAT(
                    REPEAT('*', 10),
                    SUBSTR(fb.serial_number, 11)
                ) AS masked_serial_number, fb.status AS fb_status, ct.status AS ct_status,
                sales_order.is_paid,
                sales_order.status,
                sales_order.is_gift_order,
                sales_order.is_gift_confirmed
            FROM family_bonus_pin_ticket_record_v2 fb
            LEFT JOIN customer_ticket ct
                ON fb.serial_number = ct.ticket_unique_content AND fb.sales_order_item_id = ct.sales_order_item_id 
            LEFT JOIN quote_item qi ON qi.item_id = fb.quote_item_id
            LEFT JOIN quote q ON q.entity_id = qi.quote_id
            LEFT JOIN sales_order ON sales_order.quote_id = q.entity_id
            LEFT JOIN sales_order_item ON sales_order_item.quote_item_id = qi.item_id
            WHERE fb.status != 0
              AND (ct.status IS NULL OR fb.status <> ct.status)
              AND sales_order.status != 'gift_info_pending'
        ";
    }

    /**
     * Get count statistics SQL
     *
     * @return string
     */
    protected function getCountSql(): string
    {
        return "
            SELECT 'fami_main' AS 'type', count(DISTINCT `serial_number`) AS cnt FROM family_bonus_pin_ticket_record_v2 WHERE STATUS != 0
            UNION ALL
            SELECT 'fami_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `serial_number` FROM family_bonus_pin_ticket_record_v2 WHERE STATUS != 0)
            UNION ALL
            SELECT 'yoxi_main' AS 'type', count(DISTINCT `serial_number`) AS cnt FROM yoxi_ticket_record_v2 WHERE STATUS != 0
            UNION ALL
            SELECT 'yoxi_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `serial_number` FROM yoxi_ticket_record_v2 WHERE STATUS != 0)
            UNION ALL
            SELECT 'general_non_main' AS 'type', count(DISTINCT `serial_number`) AS cnt FROM general_non_notify_ticket_record WHERE STATUS != 0
            UNION ALL
            SELECT 'general_non_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `serial_number` FROM general_non_notify_ticket_record WHERE STATUS != 0)
            UNION ALL
            SELECT 'general_main' AS 'type', count(DISTINCT `serial_number`) AS cnt FROM general_notify_ticket_record WHERE STATUS != 0
            UNION ALL
            SELECT 'general_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `serial_number` FROM general_notify_ticket_record WHERE STATUS != 0)
            UNION ALL
            SELECT 'edenred_main' AS 'type', count(DISTINCT `edenred_voucher_no`) AS cnt FROM edenred_ticket_record WHERE STATUS != 0
            UNION ALL
            SELECT 'edenred_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `edenred_voucher_no` FROM edenred_ticket_record WHERE STATUS != 0)
            UNION ALL
            SELECT 'event_ticket_main' AS 'type', count(DISTINCT `serial_number`) AS cnt FROM ticket_event_ticket WHERE STATUS != 0
            UNION ALL
            SELECT 'event_ticket_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `serial_number` FROM ticket_event_ticket WHERE STATUS != 0)
            UNION ALL
            SELECT 'openhub_main' AS 'type', count(DISTINCT `serial_number`) AS cnt FROM openhub_ticket_record WHERE STATUS != 0
            UNION ALL
            SELECT 'openhub_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `serial_number` FROM openhub_ticket_record WHERE STATUS != 0)
            UNION ALL
            SELECT 'qware_main' AS 'type', count(DISTINCT `qware_sn`) AS cnt FROM qware_ticket_record WHERE STATUS != 0
            UNION ALL
            SELECT 'qware_ct' AS 'type', count(DISTINCT ticket_unique_content) AS cnt FROM customer_ticket WHERE ticket_unique_content IN ( SELECT DISTINCT `qware_sn` FROM qware_ticket_record WHERE STATUS != 0)
        ";
    }

    /**
     * Get count statistics data
     *
     * @return array
     */
    protected function getCountData(): array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            return $connection->fetchAll($this->getCountSql());
        } catch (\Exception $e) {
            $this->logger->error('Count SQL execution failed', [
                'report' => $this->getName(),
                'error' => $e->getMessage()
            ]);
            return [];
        }
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

        // 加上統計數據
        $countData = $this->getCountData();
        if (!empty($countData)) {
            $countLines = [];
            foreach ($countData as $row) {
                $countLines[] = $row['type'] . ":" . $row['cnt'];
            }
            $text .= "\n\ncount:\n" . implode("\n", $countLines);
        }

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
