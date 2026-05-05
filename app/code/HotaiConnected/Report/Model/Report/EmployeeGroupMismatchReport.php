<?php

namespace HotaiConnected\Report\Model\Report;

class EmployeeGroupMismatchReport extends AbstractReport
{
    public function getName(): string
    {
        return '員工身份但組別不符報表 (group_id != 20)';
    }

    protected function getSql(): string
    {
        return "
            SELECT DISTINCT ce.entity_id
            FROM customer_pending_employee cpee
            LEFT JOIN customer_entity ce ON ce.phone_number = cpee.cellphone
            WHERE ce.entity_id IS NOT NULL
            AND cpee.isEnabled = 1
            AND ce.group_id != 20
        ";
    }

    public function formatForSlack(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $entityIds = array_column($data, 'entity_id');
        $text = "偵測到員工身份但組別不符的會員 ID：\n" . implode(', ', $entityIds);

        return [
            'channel' => '#sql-monitor',
            'username' => 'Employee Monitor Bot',
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
