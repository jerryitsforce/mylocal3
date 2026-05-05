<?php
declare (strict_types = 1);

namespace Branch8\Sales\Observer;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Sales\Model\Actions\GetOrderStateByStatus;
use Branch8\Sales\Model\SubOrderStatusResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as OrderHelper;

/**
 * Dispatcher for the `AutoChangeOrderStatusForNewOrder` event.
 */
class AutoChangeOrderStatusForNewOrderObserver implements ObserverInterface
{
    private SubOrderStatusResolver $subOrderStatusResolver;
    private GetOrderStateByStatus $getStateByStatus;
    private LoggerInterface $logger;
    private OrderHelper $orderHelper;

    /**
     * @param SubOrderStatusResolver $statusResolver
     * @param GetOrderStateByStatus $getOrderStateByStatus
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubOrderStatusResolver $statusResolver,
        GetOrderStateByStatus $getOrderStateByStatus,
        LoggerInterface $logger,
        OrderHelper $orderHelper
    ) {
        $this->logger                 = $logger;
        $this->subOrderStatusResolver = $statusResolver;
        $this->getStateByStatus       = $getOrderStateByStatus;
        $this->orderHelper        = $orderHelper;
    }

    /**
     * Handle the `AutoChangeOrderStatusForNewOrder` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        $quote = $observer->getData('quote');

        if ($order) {
            $currentState = $order->getState();
            $status       = $currentStatus       = $order->getStatus();
            $method       = $order->getPayment()->getMethod();
            $this->logger->info($method . '|' . $currentState . '|' . $currentStatus);
            if ($method === 'hotaipay' && $status == 'pending') {
                return;
            }

            if ($currentStatus !== Status::STATUS_PENDING_PAYMENT) {
                if ($currentState === Order::STATE_NEW) {
                    $status = Status::STATUS_PROCESSING;

                    if ($quote->getData('is_gift_order')) {
                        $status = Status::STATUS_PENDING_PAYMENT;
                    }
                }
                if ($status === $currentStatus) {
                    return;
                }
                $state = $this->getStateByStatus->getStateByStatus($status);
                $order->setState($state);
                $order->setStatus($status);
                $order->addCommentToStatusHistory(
                    __('Order auto change to "%1" by setting', $status)
                    ,
                    $status, false
                );
                $order->getResource()->save($order);

                $this->orderHelper->updateItemStatusBySubOrderStatus($order);
            }
        }
    }
}
