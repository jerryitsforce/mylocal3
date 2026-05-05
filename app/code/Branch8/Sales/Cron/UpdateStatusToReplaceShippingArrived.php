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
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\Collection as RmaShippingCollection;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;

//配送中 -> 已送達
class UpdateStatusToReplaceShippingArrived
{

    const LOG_PATH = 'Sales/Cron/updateStatusToReplaceShippingArrived';
    
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

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder $parentOrder */
    protected $parentOrder;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory */
    protected $parentOrderFactory;

    /** @var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    /** @var \Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\Collection $rmaShippingCollection */
    protected $rmaShippingCollection;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    public function __construct(
        Logger $logger,
        State $state,
        LoggerHelper $loggerhelper,
        UpdateOrderStatus $updateOrderStatus,
        RmaActions $rmaActions,
        RmaShippingCollection $rmaShippingCollection,
        HotaiCoreCommon $hotaiCoreCommon
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->rmaActions = $rmaActions;
        $this->rmaShippingCollection = $rmaShippingCollection;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {

        $this->hotaiCoreCommon->writeLog(
            "------Start Of Cron-Update-Status-To-Replace-Shipping-Arrived-----",
            self::LOG_PATH
        );

        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getItems() as $item) {
            try {
                $rmaId = $item->getParentId();

                $this->hotaiCoreCommon->writeLog(
                    "------Rma Id: $rmaId-----",
                    self::LOG_PATH
                );

                $items = $this->rmaActions->getRmaItemCollection($rmaId);

                foreach($items as $singleItem) {
                    $order = $singleItem->getOrder();
                    $status = $this->rmaActions->changeItemStatusBySalesCron(
                        $singleItem->getId(), RmaStatus::REPLACE_SHIPPING_ARRIVED
                    );
    
                    $singleItem->setFlowStatus($status);
                    $singleItem->save();
    
                    $this->updateOrderStatus->addItemStatusRecord(
                        $order->getId(), 
                        $singleItem, 
                        $status
                    );
                }

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
            "------End Of Cron-Update-Status-To-Replace-Shipping-Arrived-----",
            self::LOG_PATH
        );
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

        $collection = $this->rmaShippingCollection
            ->addFieldToFilter(
                'created_at', [
                    'from' => $from,
                    'to' => $to]
            )->addFieldToFilter(
                'status', Status::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER
            );

        return $collection;
    }
}
