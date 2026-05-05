<?php
declare(strict_types=1);
namespace Branch8\HotaiOrderNumber\Observer;

use Magento\Framework\Event\ObserverInterface;
use Branch8\HotaiOrderNumber\Model\Actions\HotaiGenerateIncrementIdForOrders;

/**
 * Verify order payment observer
 */
class SetHotaiChildOrderNumberIfMissing implements ObserverInterface
{
    /**
     * Set forced canCreditmemo flag
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException in case order has no payment specified.
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order
            && empty($order->getData(HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_NUMBER))
        ) {
           $order->setData(
               HotaiGenerateIncrementIdForOrders::FIELD_NAME_HOTAI_CHILD_ORDER_NUMBER,
               $order->getIncrementId(),
           );
        }
        return $this;
    }
}
