<?php

namespace HotaiConnected\Report\Model\Report;

class QwareTicketInfoMissingReport extends AbstractReport
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Qware 票券資訊缺失報表 (URL 或密碼為空)';
    }

    /**
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT so.increment_id
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            LEFT JOIN customer_entity ce2 ON ce2.entity_id = so.customer_id
            LEFT JOIN customer_entity ce ON ce.phone_number = so.recipient_telephone
            LEFT JOIN customer_ticket ct ON ct.sales_order_item_id = soi.item_id
            LEFT JOIN qware_ticket_record qw ON qw.`sales_order_item_id` = ct.`sales_order_item_id` AND qw.`qware_sn` = ct.ticket_unique_content
            WHERE ct.ticket_table_name = 'qware_ticket_record'
            AND (qw.qware_url = '' OR qw.qware_pwd = '')
        ";
    }

    /**
     * @param array $data
     * @return array
     */
    public function formatForSlack(array $data): array
    {
        if (empty($data)) {
            return []; // 沒資料就不發通知，保持頻道乾淨
        }

        $incrementIds = array_column($data, 'increment_id');
        $text = "偵測到 Qware 票券資訊缺失的訂單：\n" . implode(', ', array_unique($incrementIds));

        return [
            'channel' => '#sql-monitor',
            'username' => 'Qware Monitor Bot',
            'attachments' => [
                [
                    'color' => '#FF0000', // 紅色警告
                    'title' => $this->getName(),
                    'text' => $text,
                    'footer' => 'SQL Monitor Bot'
                ]
            ]
        ];
    }
}
