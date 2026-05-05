<?php

namespace HotaiConnected\Report\Model;

use HotaiConnected\Report\Helper\Config;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class SlackNotifier
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Config $config
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public const TYPE_REPORT = 'report';
    public const TYPE_COMMAND = 'command';

    /**
     * Send message to Slack
     *
     * @param array $message
     * @param string $type Webhook type: 'report' or 'command'
     * @return bool
     */
    public function send(array $message, string $type = self::TYPE_REPORT): bool
    {
        try {
            $payload = json_encode($message);

            $this->logger->info('Sending Slack notification', [
                'type' => $type,
                'payload' => $payload
            ]);

            $webhookUrl = $this->getWebhookUrl($type);
            if (empty($webhookUrl)) {
                $this->logger->error('Slack webhook URL is not configured', ['type' => $type]);
                return false;
            }

            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $this->curl->post($webhookUrl, $payload);

            $response = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            if ($statusCode === 200 && $response === 'ok') {
                $this->logger->info('Slack notification sent successfully', [
                    'type' => $type,
                    'status_code' => $statusCode,
                    'response' => $response
                ]);
                return true;
            } else {
                $this->logger->error('Slack notification failed', [
                    'type' => $type,
                    'status_code' => $statusCode,
                    'response' => $response
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send Slack notification', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get webhook URL by type
     *
     * @param string $type
     * @return string
     */
    private function getWebhookUrl(string $type): string
    {
        return $type === self::TYPE_COMMAND
            ? $this->config->getSlackCommandWebhookUrl()
            : $this->config->getSlackReportWebhookUrl();
    }

    /**
     * Send multiple messages to Slack
     *
     * @param array $messages
     * @return void
     */
    public function sendMultiple(array $messages): void
    {
        foreach ($messages as $message) {
            $this->send($message);
            // 避免 rate limit，每個訊息間隔 1 秒
            sleep(1);
        }
    }
}
