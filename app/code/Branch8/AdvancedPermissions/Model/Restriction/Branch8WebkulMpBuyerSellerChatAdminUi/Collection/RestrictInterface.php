<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Branch8WebkulMpBuyerSellerChatAdminUi\Collection;

use Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Profiles\Grid\Collection as ChatProfileGridCollection;

interface RestrictInterface
{
    public function execute(ChatProfileGridCollection $collection): void;
}
