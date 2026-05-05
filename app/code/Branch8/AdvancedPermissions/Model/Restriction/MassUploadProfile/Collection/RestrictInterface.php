<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\MassUploadProfile\Collection;

use Webkul\MpMassUpload\Model\ResourceModel\Profile\Collection as ProfileCollection;
use Webkul\MpMassUpload\Model\ResourceModel\Profile\Grid\Collection as ProfileGridCollection;

interface RestrictInterface
{
    public function execute(ProfileCollection|ProfileGridCollection $profileCollection): void;
}
