<?php

namespace Branch8\AppNotification\Model\Service;

use Branch8\AppNotification\Api\NotificationServiceInterface;
use Branch8\AppSettings\Helper\Data;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class FcmNotificationService implements NotificationServiceInterface
{
    const MAX_RETRIES =  3;
    const MAX_PER_BATCH = 500;

    private ?\Kreait\Firebase\Contract\Messaging $messaging = null;
    private LoggerInterface $logger;
    private Data $configHelper;

    public function __construct(
        LoggerInterface $logger,
        Data $configHelper
    ) {
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->init();
    }

    protected function init()
    {
        try {
            $credentials = $this->configHelper->getFirebaseServiceAccountCredentialJson();

            if (!$credentials || !str_starts_with(trim($credentials), '{')) {
                throw new \Exception('Invalid or missing Firebase service account JSON');
            }

            $factory = (new Factory)->withServiceAccount($credentials);

            $this->messaging = $factory->createMessaging();

        } catch (\Throwable $e) {
            $this->logger->error('[FCM Init Error] ' . $e->getMessage());
        }
    }


    public function send(string $deviceToken, string $title, string $body, array $data = [], $badge = null)
    {
        if (!$this->messaging) {
            $this->logger->error('[FCM] Messaging service not initialized.');
            return $this;
        }

        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data)
            ->toToken($deviceToken);

        if ($badge !== null) {
            $message = $message->withApnsConfig($this->createApnsConfig($badge));
        }


        $attempt = 0;
        while ($attempt < self::MAX_RETRIES) {
            try {
                $this->messaging->send($message);
                $this->logger->info("[FCM] Notification sent to {$deviceToken} on attempt " . ($attempt + 1));
                return $this;
            } catch (\Throwable $e) {
                $attempt++;
                $this->logger->warning("[FCM] Attempt {$attempt} failed for token {$deviceToken}: " . $e->getMessage());
                sleep(pow(2, $attempt)); // exponential backoff
            }
        }

        $this->logger->error("[FCM Send Error] Failed to send notification to {$deviceToken} after {$attempt} attempts.");

        return $this;
    }

    public function sendBatch(array $deviceTokens, string $title, string $body, array $data = [], array $badgeByToken = [])
    {
        if (!$this->messaging) {
            $this->logger->error('[FCM] Messaging service not initialized.');
            return $this;
        }

        if (empty($deviceTokens)) {
            $this->logger->warning('[FCM] No device tokens provided for batch send.');
            return $this;
        }

        $notification = Notification::create($title, $body);

        // Build messages per token (supports per-token badge)
        $buildMessages = function(array $tokens) use ($notification, $data, $badgeByToken) {
            $messages = [];
            foreach ($tokens as $token) {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);

                if (array_key_exists($token, $badgeByToken)) {
                    $badge = max(0, (int)$badgeByToken[$token]);
                    $message = $message->withApnsConfig($this->createApnsConfig($badge));
                }

                $messages[] = $message;
            }
            return $messages;
        };

        $chunks = array_chunk($deviceTokens, self::MAX_PER_BATCH);
        foreach ($chunks as $index => $chunk) {
            $attempt = 0;
            while ($attempt < self::MAX_RETRIES) {
                try {
                    $messages = $buildMessages($chunk);
                    $report = $this->messaging->sendAll($messages);

                    $this->logger->info(sprintf(
                        '[FCM] sendAll Batch #%d (attempt %d): success: %d, failure: %d',
                        $index + 1,
                        $attempt + 1,
                        $report->successes()->count(),
                        $report->failures()->count()
                    ));

                    foreach ($report->failures()->getItems() as $failure) {
                        //TODO: retry
                        $this->logger->error('[FCM] Failed token: ' . $failure->target()->value() . ' - ' . $failure->error()->getMessage());
                    }
                    foreach ($report->successes()->getItems() as $success) {
                        $this->logger->info('[FCM] Success token: ' . $success->target()->value());
                    }

                    break; // success
                } catch (\Throwable $e) {
                    $attempt++;
                    $this->logger->warning("[FCM] sendAll Batch #".($index+1)." attempt {$attempt} failed: " . $e->getMessage());
                    sleep((int)pow(2, $attempt));
                }
            }
        }

        return $this;
    }

    protected function createApnsConfig($badge)
    {
        return ApnsConfig::fromArray([
            'headers' => ['apns-priority' => '10'],
            'payload' => [
                'aps' => [
                    'badge' => $badge,
                    'sound' => 'default'
                ]
            ],
        ]);
    }
}
