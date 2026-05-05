<?php

namespace HotaiConnected\Report\Model\Report;

class SmsErrorLogReport extends AbstractReport
{
    /**
     * Get report name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'SMS 發送失敗監控';
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    protected function getSql(): string
    {
        return "SELECT smslog_id FROM fet_sms_smslog WHERE responseCode != '00000'";
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

        $smsLogIds = array_column($data, 'smslog_id');
        $text = "偵測到 SMS 發送失敗的 Log ID：\n" . implode(', ', $smsLogIds);

        return [
            'channel' => '#sql-monitor',
            'username' => 'SMS Monitor Bot',
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
