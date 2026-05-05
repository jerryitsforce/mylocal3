<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

/**
 * Status constants for chat notification queue.
 */
class Status
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUSHING_QUEUE = 'pushing_queue';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILURE = 'failure';

    /**
     * Get all available statuses.
     *
     * @return string[]
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PUSHING_QUEUE,
            self::STATUS_DONE,
            self::STATUS_FAILURE
        ];
    }
}
