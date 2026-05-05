<?php
declare(strict_types=1);

namespace Branch8\Sales\Model;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\Sales\Model\Actions\GetOrderStateByStatus;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Resolve status for order
 *
 * Home Delivery:
 * pending_payment > processing > tallying > shipping > arrived > complete
 *
 * In-store Pickup (7-11):
 * pending_payment > processing > tallying > shipping > arrived > picked > complete
 */
class ParentOrderStatusResolver
{
    private $statusPriority = [
        Status::STATUS_PENDING_PAYMENT => 1,
        Status::STATUS_GIFT_INFO_PENDING => 2,
        Status::STATUS_GIFT_INFO_COMPLETE => 3,
        Status::STATUS_PROCESSING => 4,
        Status::STATUS_TALLYING => 5,
        Status::STATUS_SHIPPING => 6,
        Status::STATUS_ARRIVED => 7,
        Status::STATUS_PICKED => 8,
        Status::STATUS_COMPLETE => 9,
        Status::STATUS_CANCEL_PENDING => 10,
        Status::STATUS_CANCELED => 11,
        Status::STATUS_PARENT_ORDER_FAIELD => 12,
    ];

    private LoggerInterface $logger;

    private GetOrderStateByStatus $getOrderStateByStatus;

    /**
     * @param LoggerInterface $logger
     * @param GetOrderStateByStatus $getOrderStateByStatus
     */
    public function __construct(
        LoggerInterface       $logger,
        GetOrderStateByStatus $getOrderStateByStatus
    )
    {
        $this->getOrderStateByStatus = $getOrderStateByStatus;
        $this->logger = $logger;
    }


    /**
     * @param ParentOrder $parentOrder
     * @return array|string
     */
    public function resolve(ParentOrder $parentOrder)
    {
        $statusPriority = $this->statusPriority;
        $status = Status::STATUS_PENDING;
        try {
            if (!$parentOrder->getSubOrders() || empty($statusPriority)) {
                return $status;
            }

            /** Holded status */
            if ($parentOrder->getSubOrders()->getFirstItem()->getData('status') == Status:: STATUS_HOLDED) {
                return [
                    $this->getOrderStateByStatus->getStateByStatus($status),
                    $status
                ];
            }

            $currentPriority = $statusPriority[$parentOrder->getSubOrders()->getFirstItem()->getData('status')];
            $status = $parentOrder->getSubOrders()->getFirstItem()->getData('status');
            foreach ($parentOrder->getSubOrders() as $subOrder) {
                if (isset($statusPriority[$subOrder->getStatus()])
                    && $statusPriority[$subOrder->getStatus()] < $currentPriority) {
                    $currentPriority = $statusPriority[$subOrder->getStatus()];
                    $status = $subOrder->getStatus();
                }
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
        return [
            $this->getOrderStateByStatus->getStateByStatus($status),
            $status
        ];
    }
}
