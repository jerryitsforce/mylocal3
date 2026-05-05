<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\WebkulMarketplace\Model\ResourceModel\PreorderItems\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\Grid\Collection as PreorderItemsGridCollection;

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

    public function beforeGetAllIds(PreorderItemsGridCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
