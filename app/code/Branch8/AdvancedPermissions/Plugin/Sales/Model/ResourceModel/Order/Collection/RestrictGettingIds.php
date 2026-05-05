<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Sales\Model\ResourceModel\Order\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\Order\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;

class RestrictGettingIds
{
    /**
     * @var CollectionRestrictInterface
     */
    private $collectionRestrict;

    private bool $isJoinCalled = false;

    public function __construct(
        CollectionRestrictInterface $collectionRestrict
    ) {
        $this->collectionRestrict = $collectionRestrict;
    }

    public function beforeGetAllIds(OrderCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
