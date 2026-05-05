<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Customer\Model\ResourceModel\Customer\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\SellerCustomer\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;

class RestrictGettingIds
{
    /**
     * @var CollectionRestrictInterface
     */
    private $customerCollectionRestrict;

    private bool $isJoinCalled = false;

    public function __construct(
        CollectionRestrictInterface $customerCollectionRestrict
    ) {
        $this->customerCollectionRestrict = $customerCollectionRestrict;
    }

    public function beforeGetAllIds(CustomerCollection $subject): void
    {
        if ($this->isJoinCalled) {
            return;
        }
        $this->isJoinCalled = true;
        $subject->getSelect()->joinLeft(
            ['sog' => 'sales_order_grid'],
            'e.entity_id = sog.customer_id',
            ['order_ids' => 'group_concat(sog.entity_id)']
        )->group('e.entity_id');
        $this->customerCollectionRestrict->execute($subject);
    }
}
