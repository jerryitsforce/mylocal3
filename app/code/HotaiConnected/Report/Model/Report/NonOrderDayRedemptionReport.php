<?php

namespace HotaiConnected\Report\Model\Report;

class NonOrderDayRedemptionReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return '非下單當日兌點檢查';
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
                so.increment_id, 
                soi.item_id, 
                soi.hotai_point_deduction_point_trans_s_n, 
                soi.created_at, 
                hpar.trans_datetime 
            FROM sales_order_item soi
            INNER JOIN hotai_point_api_record hpar 
                ON hpar.trans_s_n = soi.hotai_point_deduction_point_trans_s_n
            INNER JOIN sales_order so 
                ON so.entity_id = soi.order_id
            WHERE hpar.trans_type = 1 
                AND soi.hotai_point_deduction_point_trans_s_n IS NOT NULL 
                AND so.increment_id NOT LIKE '%hotai_order%'
                AND DATE(hpar.trans_datetime) <> DATE(DATE_ADD(soi.created_at, INTERVAL 8 HOUR))
                AND so.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY soi.created_at DESC 
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
        if (empty($data)) {
            return [];
        }

        // 取得所有不重複的 increment_id
        $incrementIds = array_column($data, 'increment_id');
        $text = "偵測到非下單當日兌點的訂單：\n" . implode(', ', array_unique($incrementIds));

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
