<?php

namespace HotaiConnected\Report\Model\Report;

class UnpaidAbnormalStatusReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '沒付錢的訂單，異常訂單狀態';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "
            SELECT entity_id, state, status, rma_status, created_at, is_paid, increment_id
            FROM sales_order
            WHERE increment_id NOT LIKE '%hotai_order%'
              AND is_paid = 0
              AND status NOT IN ('canceled', 'pending_payment')
              AND rma_status NOT IN ('rma_completed')
              AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
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
        $itemList = implode(",", $entityIds);
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
