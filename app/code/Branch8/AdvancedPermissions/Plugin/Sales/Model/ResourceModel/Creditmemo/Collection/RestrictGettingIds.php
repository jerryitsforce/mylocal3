<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Sales\Model\ResourceModel\Creditmemo\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;

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

    public function beforeGetAllIds(CreditmemoCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
