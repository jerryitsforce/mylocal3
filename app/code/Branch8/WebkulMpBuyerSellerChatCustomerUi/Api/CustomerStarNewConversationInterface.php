<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatConversationInterface;

interface CustomerStarNewConversationInterface
{
    /**
     * @param int $sellerId
     * @return mixed
     */
    public function startWith(
        int $sellerId
    );
}
