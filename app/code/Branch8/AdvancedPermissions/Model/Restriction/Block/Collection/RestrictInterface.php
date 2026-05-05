<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Block\Collection;

use Magento\Cms\Model\ResourceModel\Block\Grid\Collection as BlockGridCollection;

interface RestrictInterface
{
    public function execute(BlockGridCollection $collection): void;
}
