<?php
namespace Branch8\PointMoneyCollect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // Get the order object
        $order = $observer->getEvent()->getOrder();
        // Get the customer id and the point used total
        $customerId = $order->getCustomerId();
        $pointUsedTotal = $order->getPointUsedTotal();
        // TODO: use your API to deduct the customer's points
        // For example, you can use curl to send a request to your API endpoint

        // todo: call point submittion api
//        if ($success) {
//            // Log the success message
//        } else {
//            // Throw an exception
//        }
    }
}
