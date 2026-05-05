<?php

namespace HotaiConnected\Report\Model\Report;

class VirtualStatusInconsistentReport extends AbstractReport
{
    public function getName(): string
    {
        return '訂單虛擬狀態不一致報表 (so.is_virtual != soi.is_virtual)';
    }

    protected function getSql(): string
    {
        return "
            SELECT increment_id FROM (
                SELECT so.entity_id,
                       so.increment_id,
                       soi.sku,
                       so.is_virtual AS order_is_virtual,
                       soi.is_virtual AS item_is_virtual,
                       soi.name,
                       COUNT(soi.item_id) OVER(PARTITION BY so.entity_id) AS item_count
                FROM sales_order_item soi
                LEFT JOIN sales_order so ON so.entity_id = soi.order_id
                WHERE so.entity_id IN
                    (SELECT order_id
                     FROM sales_order_item
                    )
                  AND so.is_virtual != soi.is_virtual
            ) AS subquery
            WHERE item_count = 1
            ORDER BY increment_id DESC;
        ";
    }

    public function formatForSlack(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $incrementIds = array_column($data, 'increment_id');
        $text = "偵測到虛擬狀態不一致的訂單：\n" . implode(', ', array_unique($incrementIds));

        return [
            'channel' => '#sql-monitor',
            'username' => 'Order Monitor Bot',
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
