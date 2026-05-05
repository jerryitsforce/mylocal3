<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class CopyShippingSubsidyToOrder implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        // Copy quote-level data to order
        if ($quote->getData('shipping_subsidy_config') !== null) {
            $order->setData('shipping_subsidy_config', $quote->getData('shipping_subsidy_config'));
        }
        if ($quote->getData('seller_shipping_amount') !== null) {
            $order->setData('seller_shipping_amount', $quote->getData('seller_shipping_amount'));
        }
        if ($quote->getData('platform_shipping_amount') !== null) {
            $order->setData('platform_shipping_amount', $quote->getData('platform_shipping_amount'));
        }

        // Copy item-level data to order items
        foreach ($order->getItems() as $orderItem) {
            $quoteItem = $quote->getItemById($orderItem->getQuoteItemId());
            if (!$quoteItem) {
                continue;
            }

            if ($quoteItem->getData('platform_shipping_amount') !== null) {
                $orderItem->setData('platform_shipping_amount', $quoteItem->getData('platform_shipping_amount'));
            }

            if ($quoteItem->getData('seller_shipping_amount') !== null) {
                $orderItem->setData('seller_shipping_amount', $quoteItem->getData('seller_shipping_amount'));
            }

            if ($quoteItem->getData('shipping_subsidy_config') !== null) {
                $orderItem->setData('shipping_subsidy_config', $quoteItem->getData('shipping_subsidy_config'));
            }
        }
    }
}
