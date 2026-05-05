<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\MassUpload\Model\ResourceModel\Profile\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\MassUploadProfile\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\MpMassUpload\Model\ResourceModel\Profile\Collection as ProfileCollection;

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

    public function beforeGetAllIds(ProfileCollection $subject): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
