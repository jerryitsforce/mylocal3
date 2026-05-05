<?php

namespace Branch8\WebkulMpBuyerSellerChat\Api;

interface TotalUnreadMessageInterface
{
    /**
     * @param string $chatProfileId
     * @param string $conversationUniqueId
     * @return int
     */
    public function get(
        string $chatProfileId,
        string $conversationUniqueId
    );
}
