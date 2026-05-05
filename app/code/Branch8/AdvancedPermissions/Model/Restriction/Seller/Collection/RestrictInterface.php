<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Seller\Collection;

use Webkul\Marketplace\Model\ResourceModel\Seller\Collection as SellerCollection;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection as SaleslistCollection;
use Webkul\Marketplace\Model\ResourceModel\Saleperpartner\Collection as SaleperpartnerCollection;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection as OrdersCollection;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\Collection as RmaDetailsCollection;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\Grid\Collection as RmaDetailsGridCollection;
interface RestrictInterface
{
    public function execute(SellerCollection|SaleslistCollection|SaleperpartnerCollection|ProductCollection|OrdersCollection|RmaDetailsCollection|RmaDetailsGridCollection $collection): void;
}
