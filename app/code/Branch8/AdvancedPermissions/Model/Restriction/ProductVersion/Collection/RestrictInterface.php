<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\ProductVersion\Collection;

use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\Collection as ProductVersionCollection;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\Grid\Collection as ProductVersionGridCollection;

interface RestrictInterface
{
    public function execute(ProductVersionCollection|ProductVersionGridCollection $productVersionCollection): void;
}
