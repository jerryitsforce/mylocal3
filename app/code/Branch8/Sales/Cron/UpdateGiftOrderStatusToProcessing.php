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
use Branch8\Sales\Model\Actions\ResyncOrdersToGrid;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\App\CacheInterface;

class UpdateGiftOrderStatusToProcessing
{
    const LOG_PATH = 'Sales/Cron/UpdateGiftOrderStatusToProcessing';
    const LOCK_KEY_PREFIX = 'gift_order_processing_lock_';
    const LOCK_LIFETIME = 300; // 5 minutes

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderRepository $orderRepository */
    protected $orderRepository;

    /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
    protected $orderFactory;

    /** @var \Magento\Sales\Api\Data\OrderInterface $orderInterface */
    protected $orderInterface;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var ResyncOrdersToGrid $resyncOrdersToGrid */
    private $resyncOrdersToGrid;

    /** @var \Magento\Framework\Event\ManagerInterface $eventManager */
    protected $eventManager;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder $parentOrder */
    protected $parentOrder;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory */
    protected $parentOrderFactory;

    /** @var TransactionFactory $transactionFactory */
    protected $transactionFactory;

    /** @var CacheInterface $cache */
    protected $cache;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     * @param OrderFactory $orderFactory
     * @param OrderInterface $orderInterface
     * @param UpdateOrderStatus $updateOrderStatus
     * @param HotaiCoreCommon $hotaiCoreCommon
     * @param ResyncOrdersToGrid $resyncOrdersToGrid
     * @param ManagerInterface $eventManager
     * @param ParentOrder $parentOrder
     * @param ParentOrderFactory $parentOrderFactory
     * @param TransactionFactory $transactionFactory
     * @param CacheInterface $cache
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        OrderInterface $orderInterface,
        UpdateOrderStatus $updateOrderStatus,
        HotaiCoreCommon $hotaiCoreCommon,
        ResyncOrdersToGrid $resyncOrdersToGrid,
        ManagerInterface $eventManager,
        ParentOrder $parentOrder,
        ParentOrderFactory $parentOrderFactory,
        TransactionFactory $transactionFactory,
        CacheInterface $cache
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository        = $orderRepository;
        $this->orderFactory           = $orderFactory;
        $this->orderInterface         = $orderInterface;
        $this->updateOrderStatus      = $updateOrderStatus;
        $this->hotaiCoreCommon        = $hotaiCoreCommon;
        $this->resyncOrdersToGrid     = $resyncOrdersToGrid;
        $this->eventManager           = $eventManager;
        $this->parentOrder            = $parentOrder;
        $this->parentOrderFactory     = $parentOrderFactory;
        $this->transactionFactory     = $transactionFactory;
        $this->cache                  = $cache;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute(): void
    {
        $this->hotaiCoreCommon->writeLog(
            "------Start Of Cron-UpdateGiftOrderStatusToProcessing-----",
            self::LOG_PATH
        );

        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getItems() as $order) {
            try {
                $this->processOrder($order);
            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    sprintf(
                        "Error processing order ID %s: %s",
                        $order->getId() ?? 'N/A',
                        $e->getMessage()
                    ),
                    self::LOG_PATH
                );
                continue;
            }
        }

        $this->hotaiCoreCommon->writeLog(
            "-----End Of Cron-UpdateGiftOrderStatusToProcessing----",
            self::LOG_PATH
        );
    }

    /**
     * Process a single order with race condition protection
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function processOrder($order): void
    {
        $entityId = $order->getId();
        $lockKey = self::LOCK_KEY_PREFIX . $entityId;

        // Check if order is already being processed by another process
        if ($this->isLocked($lockKey)) {
            $this->hotaiCoreCommon->writeLog(
                "Order ID $entityId is already being processed by another process, skipping",
                self::LOG_PATH
            );
            return;
        }

        try {
            // Acquire lock
            $this->acquireLock($lockKey);

            // Reload order to get latest state (optimistic locking)
            $order = $this->orderFactory->create()->load($entityId);

            $this->hotaiCoreCommon->writeLog(
                "------Order Entity Id: $entityId-----",
                self::LOG_PATH
            );

            // Double-check: Verify order still matches criteria
            if ($order->getStatus() !== Status::STATUS_GIFT_INFO_COMPLETE || !$order->getData('is_gift_order')) {
                $this->hotaiCoreCommon->writeLog(
                    "Order ID $entityId no longer matches criteria (status: {$order->getStatus()}), skipping",
                    self::LOG_PATH
                );
                return;
            }

            // Check if order is paid
            if (!$order->getData('is_paid')) {
                $this->handleUnpaidOrder($order);
                return;
            }

            // Validate and update order items
            $canUpdateStatus = $this->validateAndUpdateOrderItems($order);

            // Update order status if all validations passed
            if ($canUpdateStatus) {
                $this->updateOrderStatusWithTransaction($order, (int) $entityId);
            }
        } finally {
            // Always release lock
            $this->releaseLock($lockKey);
        }
    }

    /**
     * Handle unpaid order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function handleUnpaidOrder($order): void
    {
        $msg = 'Is Paid is False. Cannot change to Processing.';
        $order->addStatusHistoryComment(__($msg));
        $this->orderRepository->save($order);
        $this->hotaiCoreCommon->writeLog($msg, self::LOG_PATH);
    }

    /**
     * Validate and update order items
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function validateAndUpdateOrderItems($order): bool
    {
        $canUpdateStatus = true;

        foreach ($order->getAllVisibleItems() as $item) {
            // Check RMA status
            if ($item->getRmaStatus() == RmaStatus::RMA_PROCESSING) {
                $canUpdateStatus = false;
                continue;
            }

            // Check point deduction status
            if (!$this->isPointDeductionValid($item)) {
                $canUpdateStatus = false;
                $this->handleInvalidPointDeduction($order);
                continue;
            }

            // Update item status
            $this->updateOrderStatus->updateItemStatusById(
                $item->getId(),
                Status::STATUS_PROCESSING,
                $order->getId(),
                false, // createRma
                null,  // rmaId
                true   // addItemStatusRecord
            );
        }

        return $canUpdateStatus;
    }

    /**
     * Check if point deduction is valid
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    private function isPointDeductionValid($item): bool
    {
        $pointStatus = $item->getData('hotai_point_deduction_point_progress_status');
        $validStatuses = [
            HotaiPointCommon::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT,
            HotaiPointCommon::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT,
        ];

        return in_array($pointStatus, $validStatuses);
    }

    /**
     * Handle invalid point deduction
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function handleInvalidPointDeduction($order): void
    {
        $msg = 'Point deduction failed so Not changing to Processing.';
        $order->addStatusHistoryComment(__($msg));
        $this->orderRepository->save($order);
        $this->hotaiCoreCommon->writeLog($msg, self::LOG_PATH);
    }

    /**
     * Update order status with transaction to ensure atomicity
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int $entityId
     * @return void
     */
    private function updateOrderStatusWithTransaction($order, int $entityId): void
    {
        try {
            $transaction = $this->transactionFactory->create();

            // Reload order to ensure we have latest state
            $order = $this->orderFactory->create()->load($entityId);

            // Double-check status hasn't changed
            if ($order->getStatus() !== Status::STATUS_GIFT_INFO_COMPLETE) {
                $this->hotaiCoreCommon->writeLog(
                    "Order ID $entityId status changed during processing, aborting update",
                    self::LOG_PATH
                );
                return;
            }

            $order->setStatus(Status::STATUS_PROCESSING);
            $order->addStatusHistoryComment(
                __('Cron Update Gift Order Status To %1', Status::STATUS_PROCESSING)
            );

            $transaction->addObject($order);
            $transaction->save();

            $this->resyncOrdersToGrid->execute([$order->getId()]);

            $this->hotaiCoreCommon->writeLog(
                "-----Update Status to " . $order->getStatus(),
                self::LOG_PATH
            );

            // Update parent order status if exists
            $this->updateParentOrderStatus($entityId);

            // Dispatch event for invoice ticket check
            $this->eventManager->dispatch(
                "ecpay_inovice_ticket_item_arrived_check",
                [
                    "orderId" => $order->getId(),
                ]
            );

            $this->hotaiCoreCommon->writeLog(
                "-----After ticket check, status: " . $order->getStatus(),
                self::LOG_PATH
            );
        } catch (\Exception $e) {
            $this->hotaiCoreCommon->writeLog(
                "Error updating order status for ID $entityId: " . $e->getMessage(),
                self::LOG_PATH
            );
            throw $e;
        }
    }

    /**
     * Update parent order status if exists
     *
     * @param int $entityId
     * @return void
     */
    private function updateParentOrderStatus(int $entityId): void
    {
        $parentOrderId = $this->parentOrder->getParentOrder($entityId);

        if (!is_array($parentOrderId)) {
            $parentOrder = $this->parentOrderFactory->create()->load($parentOrderId, 'index_id');
            $this->updateOrderStatus->updateParentOrderLatestStatus($parentOrder);
        }
    }

    /**
     * Get order collection that needs status update
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getCheckOrderCollectionList()
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', Status::STATUS_GIFT_INFO_COMPLETE)
            ->addFieldToFilter('is_gift_order', true);
    }

    /**
     * Check if lock exists
     *
     * @param string $lockKey
     * @return bool
     */
    private function isLocked(string $lockKey): bool
    {
        return !empty($this->cache->load($lockKey));
    }

    /**
     * Acquire lock
     *
     * @param string $lockKey
     * @return void
     */
    private function acquireLock(string $lockKey): void
    {
        $this->cache->save(
            getmypid() . '|' . microtime(true),
            $lockKey,
            [],
            self::LOCK_LIFETIME
        );
    }

    /**
     * Release lock
     *
     * @param string $lockKey
     * @return void
     */
    private function releaseLock(string $lockKey): void
    {
        $this->cache->remove($lockKey);
    }
}
