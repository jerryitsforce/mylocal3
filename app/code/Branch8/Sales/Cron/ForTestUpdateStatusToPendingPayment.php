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
use Magento\Sales\Api\Data\OrderInterface;
use Exception;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollection;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Review\Controller\Adminhtml\Product\Pending;

//pending->pending_payment
class ForTestUpdateStatusToPendingPayment
{

    const LOG_PATH = 'cron/FortestupdateStatusToPendingPayment/';
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
        OrderInterface $orderInterface,
        UpdateOrderStatus $updateOrderStatus,
        RmaActions $rmaActions,
        ItemCollection $itemCollection
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->orderInterface = $orderInterface;
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

        $this->logger->info("------Start Of Cron-Update-Status-Pending-Payment-----");

        $collection = $this->getPendingOrderCollectionList();

        foreach ($collection->getItems() as $item) {
            try {
                $order = $item->getOrder();
                $itemId = $item->getItemId();
                $this->logger->info("------Order Item Id: $itemId-----");


                if ($item->getRmaStatus() != \Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE) {
                    continue;
                }


                $status = Status::STATUS_PENDING_PAYMENT;

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

        $this->logger->info("------End Of Cron-Update-Status-Pending-Payment-----");
    }

    /**
     * getPendingOrderCollectionList 取得近 5 天是 pending 的單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $collection
     */
    private function getPendingOrderCollectionList()
    {

        $now = date('Y-m-d H:i:s');
        $lastFiveDays = date('Y-m-d H:i:s', strtotime('-5 days', strtotime($now)));

        $collection = $this->itemCollection->create()
            ->addFieldToFilter(
                'updated_at', [
                    'from' => $lastFiveDays,
                    'to' => $now]
            )->addFieldToFilter(
                'flow_status', Status::STATUS_PENDING
            );

        return $collection;
    }
}
