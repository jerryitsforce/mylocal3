<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Branch8\LimitPurchased\Model\QtyCondition\LimitPurchasedCondition;

class CheckoutSubmitBefore implements ObserverInterface
{
    /**
     * @var LimitPurchasedCondition
     */
    private $limitPurchasedCondition;

    /**
     * @param LimitPurchasedCondition $limitPurchasedCondition
     */
    public function __construct(
        LimitPurchasedCondition $limitPurchasedCondition
    ) {
        $this->limitPurchasedCondition = $limitPurchasedCondition;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        if (!$quote || !$quote->getItemsCount()) {
            return;
        }

        $aggregatedQtys = [];
        foreach ($quote->getAllItems() as $item) {
            // Only validate items explicitly available to checkout (this handles split carts)
            if (!$item->getAvailableToCheckout()) {
                continue;
            }
            if ($item->getHasChildren() || $item->isDeleted()) {
                continue;
            }

            // Group by parent product ID or own product ID to handle configurable variants together
            $pid = (string)(($item->getParentItem() && $item->getParentItem()->getProductId()) ? $item->getParentItem()->getProductId() : $item->getProductId());
            if (!isset($aggregatedQtys[$pid])) {
                $aggregatedQtys[$pid] = 0.0;
            }
            $aggregatedQtys[$pid] += (float)$item->getQty();
        }

        foreach ($quote->getAllItems() as $item) {
            // Only validate items explicitly available to checkout
            if (!$item->getAvailableToCheckout() || $item->getHasChildren() || $item->isDeleted()) {
                continue;
            }

            $pid = (string)(($item->getParentItem() && $item->getParentItem()->getProductId()) ? $item->getParentItem()->getProductId() : $item->getProductId());

            // Execute limit check using aggregated qty for the product
            // LimitPurchasedCondition execute typically accepts ID string for first argument
            $result = $this->limitPurchasedCondition->execute(
                $pid, 
                0, 
                $aggregatedQtys[$pid]
            );

            if (!empty($result->getErrors())) {
                $errors = $result->getErrors();
                $message = '';
                foreach ($errors as $error) {
                    $message .= $error->getMessage() . ' ';
                }
                if ($message) {
                    throw new LocalizedException(__($message));
                }
            }
        }
    }
}
