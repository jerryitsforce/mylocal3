<?php

declare(strict_types=1);

namespace HotaiConnected\HotaiPoint\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class PointNotificationSender
{
    const XML_PATH_ENABLE = 'hotaiconnected_hotaipoint/point_notification/enable';
    const XML_PATH_API_URL = 'hotaiconnected_hotaipoint/point_notification/api_url';
    const XML_PATH_MIN_POINTS = 'hotaiconnected_hotaipoint/point_notification/min_points';

    const NOTIFICATION_TITLE = '🔔 和泰 Points 入點通知';
    const NOTIFICATION_BODY = '昨日您已獲得 100 點以上之和泰 Points 入點，請點擊查看入帳點數及專屬換購商品。👉';
    const NOTIFICATION_URL_TEMPLATE = 'https://www.hotaigo.com.tw/account/loginSuccess?receivedPoints=%s&receivedPointsTime=%s';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Check if notification is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE);
    }

    /**
     * Get minimum points threshold from config
     *
     * @return int
     */
    public function getMinPoints(): int
    {
        return (int)($this->scopeConfig->getValue(self::XML_PATH_MIN_POINTS) ?: 100);
    }

    /**
     * Send notifications for qualified records
     *
     * @param array $records Filtered records (total_points > threshold)
     * @param string $fileDate YYYYMMDD
     * @return array Result summary
     */
    public function send(array $records, string $fileDate): array
    {
        $result = [
            'total' => count($records),
            'success' => 0,
            'failed' => 0,
            'error' => null,
        ];

        if (empty($records)) {
            return $result;
        }

        $apiUrl = $this->scopeConfig->getValue(self::XML_PATH_API_URL);
        if (!$apiUrl) {
            $this->logger->error('[PointNotification] API URL not configured');
            $result['error'] = 'API URL not configured';
            $result['failed'] = count($records);
            return $result;
        }

        // Build payload
        $payload = $this->buildPayload($records, $fileDate);

        $this->logger->info('[PointNotification] Sending ' . count($payload) . ' notifications to ' . $apiUrl);

        try {
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->setTimeout(30);
            $this->curl->post($apiUrl, json_encode($payload));

            $statusCode = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();

            $this->logger->info('[PointNotification] Response', [
                'status_code' => $statusCode,
                'body' => $responseBody,
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                $result['success'] = count($records);
            } else {
                $result['failed'] = count($records);
                $result['error'] = 'HTTP ' . $statusCode . ': ' . $responseBody;
            }
        } catch (\Exception $e) {
            $this->logger->error('[PointNotification] API call failed: ' . $e->getMessage());
            $result['failed'] = count($records);
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Build notification payload
     *
     * @param array $records
     * @param string $fileDate YYYYMMDD
     * @return array
     */
    private function buildPayload(array $records, string $fileDate): array
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $createdTime = $now->format('Y-m-d\TH:i:s.v') . $now->format('P');

        $payload = [];
        foreach ($records as $record) {
            $payload[] = [
                'ONEID' => $record['one_id'],
                'TITLE' => self::NOTIFICATION_TITLE,
                'BODY' => self::NOTIFICATION_BODY,
                'URL' => sprintf(self::NOTIFICATION_URL_TEMPLATE, $record['total_points'], $fileDate),
                'CREATEDTIME' => $createdTime,
            ];
        }

        return $payload;
    }
}
