<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Order\Collection;

use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

interface RestrictInterface
{
    public function execute(OrderCollection|OrderGridCollection $collection): void;
}
