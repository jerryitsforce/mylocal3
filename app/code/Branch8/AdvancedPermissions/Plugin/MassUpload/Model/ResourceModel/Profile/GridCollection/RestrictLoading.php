<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\MassUpload\Model\ResourceModel\Profile\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\MassUploadProfile\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\MpMassUpload\Model\ResourceModel\Profile\Grid\Collection as ProfileGridCollection;
use Magento\Framework\App\RequestInterface;

class RestrictLoading
{
    private const API_MODULE_NAME = 'api';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CollectionRestrictInterface
     */
    private $collectionRestrict;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $collectionRestrict
    ) {
        $this->request = $request;
        $this->collectionRestrict = $collectionRestrict;
    }

    public function beforeLoad(ProfileGridCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->collectionRestrict->execute($subject);
    }
}
