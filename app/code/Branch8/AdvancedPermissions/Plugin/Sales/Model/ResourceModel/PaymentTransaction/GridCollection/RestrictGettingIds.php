<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Sales\Model\ResourceModel\PaymentTransaction\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Sales\Model\ResourceModel\Transaction\Grid\Collection as PaymentTransactionGridCollection;

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

    public function beforeGetAllIds(PaymentTransactionGridCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
