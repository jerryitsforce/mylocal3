<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Helper\Order\Item;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Exception;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollection;

//退貨派車回收 -> 檢驗中
class UpdateStatusToApplyingReturnReview
{

    const LOG_PATH = 'Sales/Cron/updateStatusToApplyingReturnReview';

    /** @var \Branch8\Sales\Logger\Logger $logger */
    private $logger;

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

    protected $updatedParentOrderList = [];

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder $parentOrder */
    protected $parentOrder;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory */
    protected $parentOrderFactory;

    /** @var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var \Branch8\Sales\Helper\Order\Item $itemHelper */
    protected $itemHelper;

    public function __construct(
        Logger $logger,
        State $state,
        LoggerHelper $loggerhelper,
        UpdateOrderStatus $updateOrderStatus,
        RmaActions $rmaActions,
        ItemCollection $itemCollection,
        HotaiCoreCommon $hotaiCoreCommon,
        Item $itemHelper
    ) {
        $this->logger            = $logger;
        $this->state             = $state;
        $this->loggerhelper      = $loggerhelper;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->rmaActions        = $rmaActions;
        $this->itemCollection    = $itemCollection;
        $this->hotaiCoreCommon   = $hotaiCoreCommon;
        $this->itemHelper        = $itemHelper;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->hotaiCoreCommon->writeLog(
            "------Start Of Cron-Update-Status-Applying-Return-Review-----",
            self::LOG_PATH
        );

        $collection = $this->getApplyingReturnShippingCheckOrderCollectionList();

        foreach ($collection->getItems() as $item) {
            try {
                $order  = $item->getOrder();
                $itemId = $item->getItemId();

                $this->hotaiCoreCommon->writeLog(
                    "------Order Item Id: $itemId-----",
                    self::LOG_PATH
                );

                $status = $this->rmaActions->changeItemStatusBySalesCron(
                    $itemId, RmaStatus::RETURN_REVIEW_PROCESSING
                );

                $item->setFlowStatus($status);
                $item->save();

                $this->updateOrderStatus->addItemStatusRecord(
                    $order->getId(),
                    $item,
                    $status
                );

                $this->hotaiCoreCommon->writeLog(
                    "------Updated Status To $status-----",
                    self::LOG_PATH
                );
            } catch (Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    $e->getMessage(),
                    self::LOG_PATH
                );
                continue;
            }

        }

        $this->hotaiCoreCommon->writeLog(
            "------End Of Cron-Update-Status-Applying-Replace-Review-----",
            self::LOG_PATH
        );
    }

    /**
     * getCheckOrderCollectionList 取得近 3-15 天是派車回收的單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
     */
    private function getApplyingReturnShippingCheckOrderCollectionList()
    {
        $now  = date("Y-m-d H:i:s");
        $to   = date('Y-m-d H:i:s', strtotime('-3 days', strtotime($now)));
        $from = date('Y-m-d H:i:s', strtotime('-15 days', strtotime($now)));

        $filter = [
            'from'   => $from,
            'to'     => $to,
            'status' => Status::STATUS_APPLYING_RETURN_SHIPPING,
        ];

        $itemId = $this->itemHelper->getRmaChangableItemIds($filter);

        $collection = $this->itemCollection->create()
            ->addFieldToFilter(
                'flow_status', Status::STATUS_APPLYING_RETURN_SHIPPING
            )->addFieldToFilter(
            'item_id', $itemId
        );

        return $collection;
    }
}
