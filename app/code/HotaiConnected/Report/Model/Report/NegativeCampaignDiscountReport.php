<?php

namespace HotaiConnected\Report\Model\Report;

class NegativeCampaignDiscountReport extends AbstractReport
{
    public function getName(): string
    {
        return '活動折扣異常報表 (special_price < price_incl_tax)';
    }

    protected function getSql(): string
    {
        return "
            SELECT so.increment_id 
            FROM sales_order_item soi
            LEFT JOIN sales_order so ON so.entity_id = soi.order_id
            WHERE soi.special_price < soi.price_incl_tax
        ";
    }

    public function formatForSlack(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $incrementIds = array_column($data, 'increment_id');
        $text = "偵測到活動折扣異常的訂單：\n" . implode(', ', array_unique($incrementIds));

        return [
            'channel' => '#sql-monitor',
            'username' => 'Discount Monitor Bot',
            'attachments' => [
                [
                    'color' => '#FF0000',
                    'title' => $this->getName(),
                    'text' => $text,
                    'footer' => 'SQL Monitor Bot'
                ]
            ]
        ];
    }
}
