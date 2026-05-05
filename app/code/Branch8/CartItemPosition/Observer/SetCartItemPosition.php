<?php
namespace Branch8\CartItemPosition\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetCartItemPosition implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        // If adding a new item, get the next position value
        $highestPosition = 0;
        foreach ($quoteItem->getQuote()->getAllItems() as $item) {
            if ($item->getPosition() > $highestPosition) {
                $highestPosition = $item->getPosition();
            }
        }
        $quoteItem->setPosition($highestPosition + 1);
    }
}
