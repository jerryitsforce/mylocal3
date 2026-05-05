<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiPoint\Helper\Common as HotaiPointCommon;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Branch8\Sales\Model\Actions\ResyncOrdersToGrid;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\Shipping\Model\ShippingMethod;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class UpdateStatusToTallying
{
    const LOG_PATH = 'Sales/Cron/updateStatusToTallying';

    /** @var Logger $logger */
    private $logger;

    /** @var CollectionFactory $orderCollectionFactory */
    private $orderCollectionFactory;

    /** @var OrderRepository $orderRepository */
    private $orderRepository;

    /** @var UpdateOrderStatus $updateOrderStatus */
    private $updateOrderStatus;

    /** @var HotaiCoreCommon $hotaiCoreCommon */
    private $hotaiCoreCommon;

    /** @var ResyncOrdersToGrid $resyncOrdersToGrid */
    private $resyncOrdersToGrid;

    /**
     * @param Logger $logger
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     * @param UpdateOrderStatus $updateOrderStatus
     * @param HotaiCoreCommon $hotaiCoreCommon
     * @param ResyncOrdersToGrid $resyncOrdersToGrid
     */
    public function __construct(
        Logger $logger,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        UpdateOrderStatus $updateOrderStatus,
        HotaiCoreCommon $hotaiCoreCommon,
        ResyncOrdersToGrid $resyncOrdersToGrid
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->resyncOrdersToGrid = $resyncOrdersToGrid;
    }

    /**
     * Execute the cron job to update order status to TALLYING
     *
     * @return void
     */
    public function execute(): void
    {
        $this->writeLog("------Start Of Cron-Update-Status-TALLYING-----");
        
        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getData() as $data) {
            try {
                $entityId = $data['entity_id'];
                $this->writeLog("------Order Entity Id: $entityId-----");
                
                $order = $this->orderRepository->get($entityId);
                
                if (!$this->canUpdateOrderToTallying($order)) {
                    continue;
                }

                $canUpdateStatus = $this->processOrderItems($order);

                if ($canUpdateStatus) {
                    $this->updateOrderStatusToTallying($order);
                }
            } catch (\Exception $e) {
                $this->writeLog($e->getMessage());
                continue;
            }
        }

        $this->writeLog("-----End Of Cron-Update-Status-TALLYING----");
    }

    /**
     * Check if order can be updated to TALLYING status
     *
     * @param OrderInterface|Order $order
     * @return bool
     */
    private function canUpdateOrderToTallying($order): bool
    {
        if (!$order->getData('is_paid')) {
            $message = 'Is Paid is False. Cannot change to Tallying.';
            $order->addStatusHistoryComment(__($message));
            $this->orderRepository->save($order);
            $this->writeLog($message);
            return false;
        }

        return true;
    }

    /**
     * Process all order items and update their status to TALLYING if eligible
     *
     * @param OrderInterface|Order $order
     * @return bool Returns true if all items can be updated, false otherwise
     */
    private function processOrderItems($order): bool
    {
        $canUpdateStatus = true;

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->canUpdateItemToTallying($item, $order)) {
                $canUpdateStatus = false;
                continue;
            }

            $this->updateItemToTallying($order, $item);
        }

        return $canUpdateStatus;
    }

    /**
     * Check if order item can be updated to TALLYING status
     *
     * @param mixed $item
     * @param OrderInterface|Order $order
     * @return bool
     */
    private function canUpdateItemToTallying($item, $order): bool
    {
        // Check if item is in RMA processing
        if ($item->getRmaStatus() == RmaStatus::RMA_PROCESSING) {
            return false;
        }

        // Check if item flow status is PROCESSING
        if ($item->getFlowStatus() != Status::STATUS_PROCESSING) {
            return false;
        }

        // Check point deduction status
        $pointStatus = $item->getData('hotai_point_deduction_point_progress_status');
        $allowedPointStatuses = [
            HotaiPointCommon::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT,
            HotaiPointCommon::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT,
        ];

        if (!in_array($pointStatus, $allowedPointStatuses)) {
            $message = 'Point deduction failed so Not changing to Tallying.';
            $order->addStatusHistoryComment(__($message));
            $this->orderRepository->save($order);
            $this->writeLog($message);
            return false;
        }

        return true;
    }

    /**
     * Update order item status to TALLYING
     *
     * @param OrderInterface|Order $order
     * @param mixed $item
     * @return void
     */
    private function updateItemToTallying($order, $item): void
    {
        $item->setRmaStatus(RmaStatus::RETURN_OR_EXCHANGE_AVALIABLE);
        $item->setFlowStatus(Status::STATUS_TALLYING);
        $item->save();

        $this->updateOrderStatus->addItemStatusRecord(
            $order->getId(),
            $item,
            Status::STATUS_TALLYING
        );
    }

    /**
     * Update order status to TALLYING and resync to grid
     *
     * @param OrderInterface|Order $order
     * @return void
     */
    private function updateOrderStatusToTallying($order): void
    {
        $order->setStatus(Status::STATUS_TALLYING);
        $order->addStatusHistoryComment(
            __('Cron Update Order Status To %1', Status::STATUS_TALLYING)
        );
        $this->orderRepository->save($order);
        
        $this->resyncOrdersToGrid->execute([$order->getId()]);
        $this->writeLog("-----Update Status to TALLYING-----");
    }

    /**
     * Write log message
     *
     * @param string $message
     * @return void
     */
    private function writeLog(string $message): void
    {
        $this->hotaiCoreCommon->writeLog($message, self::LOG_PATH);
    }

    /**
     * Get order collection that needs status update to TALLYING
     * 取得需要變更狀態的訂單
     *
     * @return Collection
     */
    private function getCheckOrderCollectionList(): Collection
    {
        // 為防止訂單漏抓，取近 30 天的 processing 訂單
        $fromDate = date('Y-m-d 16:00:00', strtotime('-30 day'));
        $toDate = (new \DateTime('yesterday 23:59:59', new \DateTimeZone('Asia/Taipei')))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');

        $allowedShippingMethods = [
            ShippingMethod::METHOD_HOME,
            ShippingMethod::METHOD_CONVENIENCE_STORE,
        ];

        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', Status::STATUS_PROCESSING)
            ->addFieldToFilter('updated_at', ['from' => $fromDate, 'to' => $toDate])
            ->addFieldToFilter('shipping_method', ['in' => $allowedShippingMethods]);
    }
}
