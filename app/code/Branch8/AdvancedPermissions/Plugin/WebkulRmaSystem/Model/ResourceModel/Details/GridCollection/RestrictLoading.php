<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\WebkulRmaSystem\Model\ResourceModel\Details\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\Seller\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\Grid\Collection as RmaDetailsGridCollection;

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

    public function beforeLoad(RmaDetailsGridCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->collectionRestrict->execute($subject);
    }
}
