<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api;

interface LoadRecentlyChatInterface
{
    const DEFAULT = 20;

    /**
     * @param string $chatProfileId
     * @param string $conversationUniqueId
     * @param int $size
     * @return mixed
     */
    public function load(
        string $chatProfileId,
        string $conversationUniqueId,
        int    $size = self::DEFAULT
    );
}
