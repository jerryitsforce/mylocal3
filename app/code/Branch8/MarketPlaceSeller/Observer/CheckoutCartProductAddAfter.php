<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceSeller\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\MpApi\Api\SellerManagementInterface;

class CheckoutCartProductAddAfter implements ObserverInterface
{
    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerManagementInterface
     */
    private SellerManagementInterface $sellerManagement;

    /**
     * CheckoutCartProductAddAfter constructor.
     *
     * @param MarketplaceHelper $marketplaceHelper
     * @param SellerManagementInterface $sellerManagement
     */
    public function __construct(
        MarketplaceHelper         $marketplaceHelper,
        SellerManagementInterface $sellerManagement
    ) {
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerManagement = $sellerManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        $quoteItem->setData('origin_cost',floatval($product->getCost()));

        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($product->getId());

        if (empty($sellerId)) {
            return;
        }

        $quoteItem->setData('seller_id', $sellerId);

        $sellers = $this->sellerManagement->getSeller($sellerId);
        if ($sellers->getTotalCount() > 0) {
            $seller = $sellers->getItems()[0];
            $quoteItem->setData('seller_code', $seller['seller_code'] ?? null);
            $quoteItem->setData('seller_company_name', $seller['company_name'] ?? null);
        }
    }
}
