<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\WebkulRmaSystem\Model\ResourceModel\Details\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\Seller\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\Collection as RmaDetailsCollection;

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

    public function beforeGetAllIds(RmaDetailsCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
