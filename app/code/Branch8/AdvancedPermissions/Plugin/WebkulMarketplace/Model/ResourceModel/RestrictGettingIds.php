<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Plugin\WebkulMarketplace\Model\ResourceModel;

use Branch8\AdvancedPermissions\Model\Restriction\Seller\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection as OrdersCollection;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Webkul\Marketplace\Model\ResourceModel\Saleperpartner\Collection as SaleperpartnerCollection;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection as SaleslistCollection;
use Webkul\Marketplace\Model\ResourceModel\Seller\Collection as SellerCollection;

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

    public function beforeGetAllIds(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection $subject, $limit = null, $offset = null): void
    {
        $this->collectionRestrict->execute($subject);
    }

    public function beforeGetAllSellerIds(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection $subject, $limit = null, $offset = null): void
    {
        $this->collectionRestrict->execute($subject);
    }

    public function beforeGetAllOrderIds(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection $subject, $limit = null, $offset = null): void
    {
        $this->collectionRestrict->execute($subject);
    }

    public function beforeGetAllRemainOrderRowIds(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection $subject, $limit = null, $offset = null): void
    {
        $this->collectionRestrict->execute($subject);
    }

    public function beforeGetProducts(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection $subject, $limit = null, $offset = null): void
    {
        $this->collectionRestrict->execute($subject);
    }
}
