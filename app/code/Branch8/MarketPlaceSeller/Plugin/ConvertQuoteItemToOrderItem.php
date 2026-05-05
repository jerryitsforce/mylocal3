<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceSeller\Plugin;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\MpApi\Api\SellerManagementInterface;

/**
 * Class convert quote item data to order item data.
 */
class ConvertQuoteItemToOrderItem
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
     * ConvertQuoteItemToOrderItem constructor.
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
     * Convert quote item data to order item data.
     *
     * @param ToOrderItem $subject
     * @param OrderItemInterface $orderItem
     * @param AbstractItem $item
     * @param array $data
     *
     * @return OrderItemInterface
     */
    public function afterConvert(
        ToOrderItem        $subject,
        OrderItemInterface $orderItem,
        AbstractItem       $item,
        array              $data = []
    ): OrderItemInterface {
        $sellerId = $item->getData('seller_id');
        $product = $orderItem->getProduct();
        if ($product) {
            $orderItem->setData('origin_sku', $product->getSku());
            $orderItem->setData('origin_cost', $product->getCost());
        }
        if (!empty($sellerId)) {
            $orderItem->setData('seller_id', $sellerId);
            $orderItem->setData('seller_code', $item->getData('seller_code'));
            $orderItem->setData('seller_company_name', $item->getData('seller_company_name'));
            return $orderItem;
        }
        if (!$product) {
            return $orderItem;
        }

        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($product->getId());
        if (empty($sellerId)) {
            return $orderItem;
        }

        $orderItem->setData('seller_id', $sellerId);

        $sellers = $this->sellerManagement->getSeller($sellerId);
        if ($sellers->getTotalCount() == 0) {
            return $orderItem;
        }

        $seller = $sellers->getItems()[0] ?? null;
        if ($seller) {
            $orderItem->setData('seller_code', $seller['seller_code'] ?? null);
            $orderItem->setData('seller_company_name', $seller['company_name'] ?? null);
        }

        return $orderItem;
    }
}
