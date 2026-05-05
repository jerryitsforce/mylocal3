<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Cron;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Cron to process pending chat notifications.
 */
class ProcessCustomerNotificationQueue
{
    /**
     * @var ChatNotificationServiceInterface
     */
    private ChatNotificationServiceInterface $notificationService;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ChatNotificationServiceInterface $notificationService
     * @param LoggerInterface $logger
     */
    public function __construct(
        ChatNotificationServiceInterface $notificationService,
        LoggerInterface $logger
    ) {
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        try {
            $this->notificationService->processQueue();
        } catch (\Exception $e) {
            $this->logger->error('Error in chat notification cron: ' . $e->getMessage());
        }
    }
}
