<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Api;

/**
 * Interface ChatNotificationServiceInterface
 */
interface ChatNotificationServiceInterface
{
    /**
     * Notification type for chat.
     */
    public const TYPE_CHAT = 'seller_chat_replied';

    /**
     * Schedule a notification for a customer when a seller replies.
     *
     * @param int $customerId
     * @param string $conversationId
     * @param string $message
     * @param string $senderName
     * @param int|null $sellerId
     * @param int $storeId
     * @return void
     */
    public function schedule(
        int $customerId,
        string $conversationId,
        string $message,
        string $senderName,
        ?int $sellerId = null,
        int $storeId = 0
    ): void;


    /**
     * Send a specific notification from the queue.
     *
     * @param int $queueId
     * @return void
     */
    public function sendNotification(int $queueId): void;

    /**
     * Process pending notifications in the queue.
     *
     * @return int Number of notifications processed
     */
    public function processQueue(): int;

    /**
     * Clean up old processed records from the queue.
     *
     * @return int Number of records deleted
     */
    public function cleanUp(): int;
}
