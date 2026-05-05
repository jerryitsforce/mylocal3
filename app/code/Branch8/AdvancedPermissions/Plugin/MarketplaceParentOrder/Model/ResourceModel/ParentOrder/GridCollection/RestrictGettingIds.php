<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\MarketplaceParentOrder\Model\ResourceModel\ParentOrder\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\ParentOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\Grid\Collection as ParentOrderGridCollection;


class RestrictGettingIds
{
    /**
     * @var CollectionRestrictInterface
     */
    private $collectionRestrict;

    public function __construct(
        CollectionRestrictInterface $collectionRestrict
    ) {
        $this->collectionRestrict = $collectionRestrict;
    }

    public function beforeGetAllIds(ParentOrderGridCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
