<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Customer\Model\ResourceModel\Customer\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\SellerCustomer\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;

class RestrictGettingIds
{
    /**
     * @var CollectionRestrictInterface
     */
    private $customerCollectionRestrict;

    public function __construct(
        CollectionRestrictInterface $customerCollectionRestrict
    ) {
        $this->customerCollectionRestrict = $customerCollectionRestrict;
    }

    public function beforeGetAllIds(CustomerGridCollection $subject): void
    {
        $this->customerCollectionRestrict->execute($subject);
    }
}
