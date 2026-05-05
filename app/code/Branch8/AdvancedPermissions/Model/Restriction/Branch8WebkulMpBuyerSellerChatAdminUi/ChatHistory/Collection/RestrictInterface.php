<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Model\Restriction\Branch8WebkulMpBuyerSellerChatAdminUi\ChatHistory\Collection;

use Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Message\Grid\Collection as ChatHistoryGridCollection;

interface RestrictInterface
{
    public function execute(ChatHistoryGridCollection $collection): void;
}
