<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api;

interface LoadChatHistoryInterface
{
    const DEFAULT = 5;
    const PREVIOUS = 'previous';
    const NEXT = 'next';

    /**
     * @param string $chatProfileId
     * @param string $conversationUniqueId
     * @param string $anchorDate
     * @param string $direction
     * @return mixed
     */
    public function load(
        string $chatProfileId,
        string $conversationUniqueId,
        string $anchorDate,
        string $direction = self::PREVIOUS,
    );
}
