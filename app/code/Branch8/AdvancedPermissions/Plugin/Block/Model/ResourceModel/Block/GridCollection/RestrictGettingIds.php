<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Block\Model\ResourceModel\Block\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\Block\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Cms\Model\ResourceModel\Block\Grid\Collection as BlockCollection;

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

    public function beforeGetAllIds(BlockCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
