<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Customer\Model\ResourceModel\Customer\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\SellerCustomer\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;
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
    private $customerCollectionRestrict;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $customerCollectionRestrict
    ) {
        $this->request = $request;
        $this->customerCollectionRestrict = $customerCollectionRestrict;
    }

    public function beforeLoad(CustomerGridCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->customerCollectionRestrict->execute($subject);
    }
}
