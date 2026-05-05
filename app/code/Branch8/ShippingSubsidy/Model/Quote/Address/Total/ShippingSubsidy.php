<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Model\Quote\Address\Total;

use Branch8\ShippingSubsidy\Model\GetSellerByProductId;
use Branch8\ShippingSubsidy\Model\SellerSubsidy;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class ShippingSubsidy extends AbstractTotal
{
    /**
     * @param SellerSubsidy $sellerSubsidy
     * @param Json $json
     * @param GetSellerByProductId $getSellerByProductId
     */
    public function __construct(
        private readonly SellerSubsidy $sellerSubsidy,
        private readonly Json $json,
        private readonly GetSellerByProductId $getSellerByProductId
    ) {
        $this->setCode('shipping_subsidy');
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): self {

        parent::collect($quote, $shippingAssignment, $total);
        if (!$this->sellerSubsidy->isEnabled($quote->getStoreId()) || !$quote->getIsSubQuote()) {
            return $this;
        }

        $address = $shippingAssignment->getShipping()->getAddress();
        $shippingMethod = $address->getShippingMethod();
        if (!$shippingMethod) {
            return $this;
        }
        $shippingAmount = (float)$address->getShippingInclTax();
        if ($shippingAmount <= 0) {
            return $this;
        }
        $items = $shippingAssignment->getItems();
        if (!$items) {
            return $this;
        }
        $totalQty = 0;
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $totalQty += $item->getQty();
        }

        if ($totalQty <= 0) {
            return $this;
        }
        $totalPlatformAmount = 0;
        $totalSellerShippingAmount = 0;
        $config = [];
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $sellerId = $this->getSellerByProductId->get((int)$item->getProduct()->getId());
            if (!$sellerId) {
                continue;
            }
            $itemShippingAmount = ($shippingAmount * $item->getQty()) / $totalQty;
            $subsidy = $this->sellerSubsidy->calculateSubsidy($sellerId, $shippingMethod, $itemShippingAmount, $quote->getStoreId());
            $totalPlatformAmount += $subsidy['platform_amount'];
            $totalSellerShippingAmount += $subsidy['seller_amount'];
            $item->setData('platform_shipping_amount', $subsidy['platform_amount']);
            $item->setData('seller_shipping_amount', $subsidy['seller_amount']);
            if ($subsidy['config']) {
                $itemConfig = [
                    'seller_id' => $sellerId,
                    'mode' => $subsidy['config']['mode'],
                    'value' => $subsidy['config']['value'],
                ];
                $item->setData('shipping_subsidy_config', $this->json->serialize($itemConfig));
                $config[$sellerId] = $itemConfig;
            }
        }
        $quote->setData('seller_shipping_amount', $totalSellerShippingAmount);
        $quote->setData('platform_shipping_amount', $totalPlatformAmount);
        if (!empty($config)) {
            $quote->setData('shipping_subsidy_config', $this->json->serialize($config));
        }
        return $this;
    }
}
