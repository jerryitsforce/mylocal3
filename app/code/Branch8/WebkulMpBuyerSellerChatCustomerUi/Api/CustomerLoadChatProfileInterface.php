<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatConversationInterface;

interface CustomerLoadChatProfileInterface
{
    /**
     * @return mixed
     */
    public function load();
}
