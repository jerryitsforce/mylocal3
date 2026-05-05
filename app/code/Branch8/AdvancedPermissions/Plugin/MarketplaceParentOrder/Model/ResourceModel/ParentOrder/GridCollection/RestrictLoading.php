<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Plugin\MarketplaceParentOrder\Model\ResourceModel\ParentOrder\GridCollection;

use Branch8\AdvancedPermissions\Model\Restriction\ParentOrder\Collection\RestrictInterface as CollectionRestrictInterface;
use Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\Grid\Collection as ParentOrderGridCollection;


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

    public function beforeLoad(ParentOrderGridCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->collectionRestrict->execute($subject);
    }
}
