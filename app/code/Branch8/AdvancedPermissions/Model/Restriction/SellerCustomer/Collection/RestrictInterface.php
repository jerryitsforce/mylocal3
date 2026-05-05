<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\SellerCustomer\Collection;

use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;

interface RestrictInterface
{
    public function execute(CustomerCollection|CustomerGridCollection $customerCollection): void;
}
