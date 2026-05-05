<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Exception;
use \Magento\Framework\App\State;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use \Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;

//退貨派車回收 -> 檢驗中
class ForTestUpdateStatusToApplyingReturnReview
{

    const LOG_PATH = 'cron/ForTestupdateStatusToApplyingReturnReview/';
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
    /** @var \Magento\Sales\Model\ResourceModel\Order\Item\Collection $itemCollection */
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

    public function __construct(
        Logger $logger,
        State $state,
        LoggerHelper $loggerhelper,
        UpdateOrderStatus $updateOrderStatus,
        RmaActions $rmaActions,
        ItemCollection $itemCollection
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->rmaActions = $rmaActions;
        $this->itemCollection = $itemCollection;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->logger = $this->loggerhelper->setPath($this->logger, self::LOG_PATH);

        $this->logger->info("------Start Of Cron-Update-Status-Applying-Return-Review-----");

        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getItems() as $item) {
            try {
                $order = $item->getOrder();
                $itemId = $item->getItemId();

                $this->logger->info("------Order Item Id: $itemId-----");

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

                $this->logger->info("------Updated Status To $status-----");
            } catch (Exception $e) {
                $this->logger->info($e->getMessage());
                continue;
            }
            

        }

        $this->logger->info("------End Of Cron-Update-Status-Applying-Replace-Review-----");
    }

    /**
     * getCheckOrderCollectionList 取得近 3-5 天是派車回收的單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
     */
    private function getCheckOrderCollectionList()
    {
        $now = date("Y-m-d H:i:s");
        $to = date('Y-m-d H:i:s', strtotime('-3 days', strtotime($now)));
        $from = date('Y-m-d H:i:s', strtotime('-5 days', strtotime($now)));

        $collection = $this->itemCollection
            ->addFieldToFilter(
                'updated_at', [
                    'from' => $from,
                    'to' => $now]
            )->addFieldToFilter(
                'flow_status', Status::STATUS_APPLYING_RETURN_SHIPPING
            );

        return $collection;
    }
}
