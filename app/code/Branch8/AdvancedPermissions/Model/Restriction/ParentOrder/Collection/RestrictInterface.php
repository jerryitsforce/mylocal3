<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\ParentOrder\Collection;

use Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\Grid\Collection as ParentOrderGridCollection;

interface RestrictInterface
{
    public function execute(ParentOrderGridCollection $collection): void;
}
