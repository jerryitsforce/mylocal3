<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Queue;

use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api\ChatNotificationServiceInterface;

/**
 * Consumer to process chat notifications via Message Queue.
 */
class Consumer
{
    /**
     * @var ChatNotificationServiceInterface
     */
    private ChatNotificationServiceInterface $notificationService;

    /**
     * @param ChatNotificationServiceInterface $notificationService
     */
    public function __construct(
        ChatNotificationServiceInterface $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    /**
     * Send a specific notification.
     *
     * @param int $queueId
     * @return void
     */
    public function send(int $queueId): void
    {
        $this->notificationService->sendNotification($queueId);
    }
}
