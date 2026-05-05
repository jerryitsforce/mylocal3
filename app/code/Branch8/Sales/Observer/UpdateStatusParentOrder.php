<?php
declare(strict_types=1);

namespace Branch8\Sales\Observer;

use Branch8\MarketPlaceParentOrder\Model\Services\ParentOrderFinder;
use Branch8\Sales\Model\ParentOrderStatusResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolve parent order status base on change from sub order
 * @property LoggerInterface $logger
 */
class UpdateStatusParentOrder implements ObserverInterface
{

    private ParentOrderFinder $parentOrderFinder;
    private LoggerInterface $logger;

    private ParentOrderStatusResolver $parentOrderStatusResolver;

    /**
     * @param ParentOrderFinder $parentOrderFinder
     * @param ParentOrderStatusResolver $parentOrderStatusResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        ParentOrderFinder         $parentOrderFinder,
        ParentOrderStatusResolver $parentOrderStatusResolver,
        LoggerInterface           $logger
    )
    {
        $this->parentOrderFinder = $parentOrderFinder;
        $this->logger = $logger;
        $this->parentOrderStatusResolver = $parentOrderStatusResolver;
    }

    /**
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        try {
            /**
             * @TODO should process this action by cron or message queue
             */
            /**
             * @var $order \Magento\Sales\Model\Order
             */
            $order = $observer->getEvent()->getOrder();
            if (!$order->isObjectNew() && $order->getId()) {
                $parentOrderCollection = $this->parentOrderFinder->find($order);
                foreach ($parentOrderCollection as $parentOrder) {
                    list($state, $status) = $this->parentOrderStatusResolver->resolve($parentOrder);
                    $detail = $parentOrder->getDetail();
                    if($detail->getStatus() != $status) {
                        $detail->setState($state)->setStatus($status)->save();
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }
}
