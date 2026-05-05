<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Exception;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as ItemCollection;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;

//已送達 -> 已取貨
class UpdateStatusToPicked
{

    const LOG_PATH = 'Sales/Cron/updateStatusToPicked';
    
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

     /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
     protected $hotaiCoreCommon;

    public function __construct(
        Logger $logger,
        State $state,
        LoggerHelper $loggerhelper,
        UpdateOrderStatus $updateOrderStatus,
        RmaActions $rmaActions,
        ItemCollection $itemCollection,
        HotaiCoreCommon $hotaiCoreCommon
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->rmaActions = $rmaActions;
        $this->itemCollection = $itemCollection;
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
            "------Start Of Cron-Update-Status-Picked-----",
            self::LOG_PATH
        );

        $collection = $this->getArrivedCheckOrderCollectionList();

        foreach ($collection->getItems() as $item) {
            try {
                $order = $item->getOrder();
                $itemId = $item->getItemId();

                $this->hotaiCoreCommon->writeLog(
                    "------Order Item Id: $itemId-----",
                    self::LOG_PATH
                );

                if ($item->getFlowStatus() == Status::STATUS_COMPLETE) {
                    continue;
                }

                if ($item->getFlowStatus() == Status::STATUS_FAILED_DELIVERY) {
                    continue;
                }

                if ($item->getRmaStatus() != \Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE) {
                    continue;
                }

                $status = Status::STATUS_PICKED;

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
            "------End Of  Cron-Update-Status-Picked-----",
            self::LOG_PATH
        );
    }

    /**
     * getArrivedCheckOrderCollectionList 取得近 8-9 天是已送達的單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Item\Collection $collection
     */
    private function getArrivedCheckOrderCollectionList()
    {
        $now = date("Y-m-d H:i:s");
        $to = date('Y-m-d H:i:s', strtotime('-8 days', strtotime($now)));
        $from = date('Y-m-d H:i:s', strtotime('-11 days', strtotime($now)));

        $collection = $this->itemCollection->create()
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                [
                    'sales_order.shipping_method' => 'sales_order.shipping_method',
                ]
            )
            ->addFieldToFilter(
                'main_table.updated_at', [
                    'from' => $from,
                    'to' => $to]
            )->addFieldToFilter(
            'main_table.flow_status', Status::STATUS_ARRIVED
            )->addFieldToFilter(
            'sales_order.shipping_method',
            \Branch8\Shipping\Model\ShippingMethod::METHOD_CONVENIENCE_STORE
        );

        return $collection;
    }
}
