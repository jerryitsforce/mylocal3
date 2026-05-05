<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\ProductVersion\Model\ResourceModel\ProductVersion\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\ProductVersion\Collection\RestrictInterface as CollectionRestrictInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\Collection as ProductVersionCollection;

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

    public function beforeGetAllIds(ProductVersionCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
