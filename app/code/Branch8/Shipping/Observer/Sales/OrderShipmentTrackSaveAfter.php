<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Shipping\Observer\Sales;

use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\Sales\Helper\Order\Item;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\OrderFactory;

/**
 * Observer for updating order and item status after shipment track is saved
 * 當 shipment track 保存後更新訂單和訂單項狀態的觀察者
 */
class OrderShipmentTrackSaveAfter implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var UpdateOrderStatus
     */
    private $updateOrderStatus;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Item
     */
    private $itemHelper;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param UpdateOrderStatus $updateOrderStatus
     * @param ItemFactory $itemFactory
     * @param OrderFactory $orderFactory
     * @param Item $itemHelper
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        UpdateOrderStatus $updateOrderStatus,
        ItemFactory $itemFactory,
        OrderFactory $orderFactory,
        Item $itemHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->itemFactory = $itemFactory;
        $this->orderFactory = $orderFactory;
        $this->itemHelper = $itemHelper;
    }

    /**
     * Execute observer
     * After shipment track is saved, update order item flow status and order status
     * 當 shipment track 保存後，更新訂單項 flow status 和訂單狀態
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $track = $observer->getTrack();

        // Skip if arrival date is set (already arrived)
        if ($track->getArrivalDate()) {
            return;
        }

        $order = $this->loadOrder((int)$track->getOrderId());
        if (!$order || !$order->getId()) {
            return;
        }

        $shipment = $track->getShipment();
        $this->updateShipmentItemsStatus($shipment, $order);
        $this->updateOrderStatusIfNeeded($order, $track);
    }

    /**
     * Update shipment items flow status to shipping
     * 更新 shipment 中訂單項的 flow status 為 shipping
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param Order $order
     * @return void
     */
    private function updateShipmentItemsStatus($shipment, Order $order): void
    {
        foreach ($shipment->getItemsCollection() as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();

            // Skip items that are in reverse flow (RMA processing)
            if ($this->itemHelper->isRmaProcessing($orderItem->getItemId())) {
                continue;
            }

            $this->updateOrderStatus->updateItemStatusById(
                $orderItem->getItemId(),
                Status::STATUS_SHIPPING,
                $order->getEntityId()
            );
            $orderItem->setFlowStatus($this->updateOrderStatus->getFlowStatus());
            $orderItem->save();
        }
    }

    /**
     * Update order status to shipping if conditions are met
     * 如果滿足條件，將訂單狀態更新為 shipping
     *
     * @param Order $order
     * @param \Magento\Sales\Model\Order\Shipment\Track $track
     * @return void
     */
    private function updateOrderStatusIfNeeded(Order $order, $track): void
    {
        if (!$this->isOneOfOrderItemInFormalFlow($order)) {
            return;
        }

        $comment = $this->buildStatusComment($order, $track);
        if ($comment) {
            $order->addCommentToStatusHistory($comment);
            $this->orderRepository->save($order);
        }
    }

    /**
     * Build status comment and update order status if needed
     * 構建狀態註釋，如果需要則更新訂單狀態
     *
     * @param Order $order
     * @param \Magento\Sales\Model\Order\Shipment\Track $track
     * @return string
     */
    private function buildStatusComment(Order $order, $track): string
    {
        $comment = '';

        // Update order status to shipping if no items are in processing or tallying
        if (!$this->hasItemInProcessingOrTallying($order)) {
            $order->setState(State::STATE_PROCESSING);
            $order->setStatus(Status::STATUS_SHIPPING);
            $comment = __("Update Status to %1.", Status::STATUS_SHIPPING);
        }

        // Add tracking number to comment if available
        $trackNumber = $track->getTrackNumber();
        if ($trackNumber) {
            $trackingComment = __(" Tracking Number is %1.", $trackNumber);
            $comment = $comment ? $comment . $trackingComment : $trackingComment;
        }

        return (string)$comment;
    }

    /**
     * Check if order has at least one item in formal flow (not in RMA processing)
     * 檢查訂單中是否有至少一個非逆流程的訂單項（不在 RMA 處理中）
     *
     * @param Order $order
     * @return bool
     */
    private function isOneOfOrderItemInFormalFlow(Order $order): bool
    {
        if ($order->getStatus() === Status::STATUS_CANCELED) {
            return false;
        }

        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getRmaStatus() !== RmaStatus::RMA_PROCESSING) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if any order item's flow status is processing or tallying
     * 檢查是否有任何訂單項的 flow status 是 processing 或 tallying
     *
     * @param Order $order
     * @return bool
     */
    private function hasItemInProcessingOrTallying(Order $order): bool
    {
        foreach ($order->getAllVisibleItems() as $item) {
            $flowStatus = $item->getFlowStatus();
            if ($flowStatus === Status::STATUS_PROCESSING || $flowStatus === Status::STATUS_TALLYING) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load order by ID
     * 根據 ID 載入訂單
     *
     * @param int $orderId
     * @return Order|null
     */
    private function loadOrder(int $orderId): ?Order
    {
        try {
            return $this->orderFactory->create()->load($orderId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
