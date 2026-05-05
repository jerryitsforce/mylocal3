<?php
declare(strict_types=1);

namespace Branch8\Sales\Model;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Sales\Model\Actions\GetOrderStateByStatus;
use Magento\Sales\Model\Order;

/**
 * Resolve status for order
 *
 * Home Delivery:
 * pending_payment > processing > tallying > shipping > arrived > complete
 *
 * In-store Pickup (7-11):
 * pending_payment > processing > tallying > shipping > arrived > picked > complete
 */
class SubOrderStatusResolver
{
    private GetOrderStateByStatus $getOrderStateByStatus;

    /**
     * @param GetOrderStateByStatus $getOrderStateByStatus
     */
    public function __construct(GetOrderStateByStatus $getOrderStateByStatus)
    {
        $this->getOrderStateByStatus = $getOrderStateByStatus;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function resolve(\Magento\Sales\Model\Order $order)
    {
        if ($order->isObjectNew()) {
            $status = $this->resolveStatusForNewOrder($order);
        } else {
            $status = $this->resolveStatusForOrder($order);
        }
        return [
            $this->getOrderStateByStatus->getStateByStatus($status),
            $status
        ];
    }

    /**
     * @param Order $order
     * @return float|string|null
     */
    private function resolveStatusForOrder(\Magento\Sales\Model\Order $order)
    {
        $state = $order->getState();
        if ($state === Order::STATE_PROCESSING) {
            /****
             * keep order in status processing when create invoice + shipment from BE
             * "complete" order will to process by cron  handle by jane
             ********/
            return $order->getStatus();
        }
        return $order->getStatus();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    private function resolveStatusForNewOrder(\Magento\Sales\Model\Order $order)
    {
        $currentStatus = $order->getStatus();
        if ($currentStatus === Status::STATUS_PENDING_PAYMENT) {
            return Status::STATUS_PENDING_PAYMENT;
        }
        $status = Status::STATUS_PROCESSING;
        if ($this->isVirtualOrder($order)) {
            $status = Status::STATUS_PROCESSING;
        }
        return $status;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return float|int|null
     */
    private function isVirtualOrder(\Magento\Sales\Model\Order $order)
    {
        return (bool)$order->getIsVirtual();
    }
}
