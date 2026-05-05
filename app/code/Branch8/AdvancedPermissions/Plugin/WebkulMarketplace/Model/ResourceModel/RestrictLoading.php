<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Plugin\WebkulMarketplace\Model\ResourceModel;

use Branch8\AdvancedPermissions\Model\Restriction\Seller\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\Collection as SellerCollection;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection as SaleslistCollection;
use Webkul\Marketplace\Model\ResourceModel\Saleperpartner\Collection as SaleperpartnerCollection;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection as OrdersCollection;

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

    public function beforeLoad(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->collectionRestrict->execute($subject);
    }
}
