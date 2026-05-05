<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Branch8\Sales\Model\Actions\ResyncOrdersToGrid;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollection;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Magento\Framework\Registry;


class UpdateParentAndSubOrderStatus
{

    const LOG_PATH = 'Sales/Cron/updateParentAndSubOrderStatus';

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var \Magento\Sales\Model\OrderRepository $orderRepository */
    protected $orderRepository;

    /** @var \Magento\Sales\Model\OrderFactory $order */
    protected $order;

    /** @var \Magento\Framework\App\State $state */
    protected $state;

    /** @var \Branch8\Sales\Helper\Logger $loggerhelper */
    protected $loggerhelper;

    /** @var \Magento\Sales\Api\Data\OrderInterface $orderInterface */
    protected $orderInterface;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollection */
    protected $itemCollection;

    /** @var array $updatedSubOrderList */
    protected $updatedSubOrderList = [];

    /** @var array $updatedParentOrderList */
    protected $updatedParentOrderList = [];

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder $parentOrder */
    protected $parentOrder;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory */
    protected $parentOrderFactory;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var Registry */
    protected $registry;
    private ResyncOrdersToGrid $resyncOrdersToGrid;

    public function __construct(
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        OrderFactory $order,
        State $state,
        OrderInterface $orderInterface,
        UpdateOrderStatus $updateOrderStatus,
        ItemCollection $itemCollection,
        ParentOrder $parentOrder,
        ParentOrderFactory $parentOrderFactory,
        HotaiCoreCommon $hotaiCoreCommon,
        Registry $registry,
        ResyncOrdersToGrid $resyncOrdersToGrid
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->state = $state;
        $this->orderInterface = $orderInterface;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->itemCollection = $itemCollection;
        $this->parentOrder = $parentOrder;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->registry = $registry;
        $this->resyncOrdersToGrid = $resyncOrdersToGrid;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->registry->register('is_cron_status_update', true);

        $this->hotaiCoreCommon->writeLog(
            "------Start Of Cron-Update-Parent-And-Sub-Order-Status-----",
            self::LOG_PATH
        );

        $collection = $this->getCheckOrderCollectionList();
        $this->updateSubOrderStatus($collection);
        $this->updateParentOrderStatus();

        $this->hotaiCoreCommon->writeLog(
            "------End Of Cron-Update-Parent-And-Sub-Order-Status-----",
            self::LOG_PATH
        );
    }

    /**
     * updateParentOrderStatus
     *
     * @return void
     */
    protected function updateParentOrderStatus()
    {
        foreach ($this->updatedSubOrderList as $subOrderId => $parentOrderId) {

            try {
                $parentOrderId = $parentOrderId;
                if (in_array($parentOrderId, $this->updatedParentOrderList)) {
                    continue;
                }

                if(is_array($parentOrderId)) {
                    continue;
                }

                $this->hotaiCoreCommon->writeLog(
                    "------Parent Order Entity Id: $parentOrderId-----",
                    self::LOG_PATH
                );

                $parentOrder = $this->parentOrderFactory->create()->load($parentOrderId, 'index_id');
                $status = $this->updateOrderStatus->updateParentOrderLatestStatus($parentOrder);
                $this->updatedParentOrderList[] = $parentOrderId;

                $this->hotaiCoreCommon->writeLog(
                    "------Updated Parent Order Status To: $status -----",
                    self::LOG_PATH
                );

            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    $e->getMessage(),
                    self::LOG_PATH
                );
                continue;
            }

        }

    }

    /**
     * updateSubOrderStatus
     *
     * @param  \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
     * @return void
     */
    protected function updateSubOrderStatus($collection)
    {
        foreach ($collection->getItems() as $item) {
            try {

                $order = $this->order->create()->load($item->getOrderId());
                if (in_array($order->getId(), $this->updatedSubOrderList)) {
                    continue;
                }

                $entityId = $order->getId();
                $this->resyncOrdersToGrid->execute([$order->getId()]);
                $this->hotaiCoreCommon->writeLog(
                   "------Order Entity Id: $entityId-----",
                    self::LOG_PATH
                );

                $status = $this->updateOrderStatus->updateSubOrderLatestStatus($order);
                $this->updatedSubOrderList[$entityId] = $this->parentOrder->getParentOrder((int) $entityId);

                $this->hotaiCoreCommon->writeLog(
                    "------Updated Order Status To: $status -----",
                     self::LOG_PATH
                 );

            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    $e->getMessage(),
                     self::LOG_PATH
                 );
                continue;
            }
        }
    }

    /**
     * getCheckOrderCollectionList 取得需要變更狀態的訂單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
     */
    private function getCheckOrderCollectionList()
    {
        $now = date('Y-m-d H:i:s');
        $lastTwentyMinutes = date('Y-m-d H:i:s', strtotime('-20 minutes', strtotime($now)));

        $collection = $this->itemCollection->create()
            ->join(
                ['sales_parent_order_children' => 'sales_parent_order_children'],
                    'main_table.order_id = sales_parent_order_children.children_id',
                [
                    'sales_parent_order_children.children_id' => 'sales_parent_order_children.children_id'
                ]
            )
            ->addFieldToFilter(
                'main_table.updated_at', [
                    'from' => $lastTwentyMinutes,
                    'to' => $now]
            );

        return $collection;
    }
}
