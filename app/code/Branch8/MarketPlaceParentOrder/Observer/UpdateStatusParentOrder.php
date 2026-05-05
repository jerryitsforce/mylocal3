<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Observer;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\Services\AssignDataForParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\CopyAddressesFromSalesOrderToParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\ParentOrderFinder;
use Branch8\MarketPlaceParentOrder\Model\Services\ParentOrderStatusResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderRepository;
use Webkul\Mpsplitorder\Model\Mpsplitorder;

/**
 * Resolve parent order status base on change from sub order
 */
class UpdateStatusParentOrder implements ObserverInterface
{

    private ParentOrderFinder $parentOrderFinder;
    private Log $log;

    private ParentOrderStatusResolver $parentOrderStatusResolver;

    /**
     * @param ParentOrderFinder $parentOrderFinder
     * @param ParentOrderStatusResolver $parentOrderStatusResolver
     * @param Log $log
     */
    public function __construct(
        ParentOrderFinder         $parentOrderFinder,
        ParentOrderStatusResolver $parentOrderStatusResolver,
        Log                       $log
    )
    {
        $this->parentOrderFinder = $parentOrderFinder;
        $this->log = $log;
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
                    $this->parentOrderStatusResolver->resolve($parentOrder);
                }
            }
        } catch (\Throwable $exception) {
            $this->log->logException('UpdateStatusParentOrder', $exception);
        }
    }
}
