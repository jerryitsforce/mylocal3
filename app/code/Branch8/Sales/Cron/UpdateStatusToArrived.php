<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Logger\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use \Branch8\Shipping\Model\ShippingMethod;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollection;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;

class UpdateStatusToArrived
{

    const LOG_PATH = 'Sales/Cron/updateStatusToArrived';
    
    /**  @var \Branch8\Sales\Logger\Logger $logger */
    private $logger;
    
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;
    
    /**　@var \Magento\Sales\Model\OrderRepository $orderRepository */
    protected $orderRepository;
    
    /** @var \Magento\Sales\Model\Order $order */
    protected $order;
    
    /** @var \Magento\Framework\App\State $state */
    protected $state;
    
    /** @var \Branch8\Sales\Helper\Logger $loggerhelper */
    protected $loggerhelper;
    
    /** @var \Magento\Sales\Api\Data\OrderInterface $orderInterface */
    protected $orderInterface;
    
    /** @var mixed $shipment */
    protected $shipment;
    
    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;
    
    /** @var  \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory */
    protected $trackingCollection;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    public function __construct(
        Logger $logger,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        Order $order,
        State $state,
        LoggerHelper $loggerhelper,
        OrderInterface $orderInterface,
        ShipmentCollection $shipment,
        UpdateOrderStatus $updateOrderStatus,
        TrackCollectionFactory $collectionFactory,
        HotaiCoreCommon $hotaiCoreCommon
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->orderInterface = $orderInterface;
        $this->shipment = $shipment;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->trackingCollection = $collectionFactory;
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
            "------Start Of Cron-Update-Status-Arrived-----",
            self::LOG_PATH
        );

        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getItems() as $data) {
            try {
                $shipment = $data->getShipment();

                foreach ($shipment->getItemsCollection() as $item) {
                    $item = $item->getOrderItem();

                    if ($item->getFlowStatus() == Status::STATUS_ARRIVED) {
                        continue;
                    }

                    if ($item->getFlowStatus() == Status::STATUS_FAILED_DELIVERY) {
                        continue;
                    }

                    if($item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_NOT_AVALIABLE) {
                        $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE);
                        $item->save();
                    }

                    if ($item->getRmaStatus() != \Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE) {
                        continue;
                    }


                    $itemId = $item->getId();
                    
                    $this->hotaiCoreCommon->writeLog(
                        "------Order Item Entity Id: $itemId-----",
                        self::LOG_PATH
                    );

                    $order = $item->getOrder();
                    $item->setFlowStatus(Status::STATUS_ARRIVED);
                    $item->save();
                    $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, Status::STATUS_ARRIVED);
                    
                    $this->hotaiCoreCommon->writeLog(
                        "------Update Status to Arrived-----",
                        self::LOG_PATH
                    );
                }

                

            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    $e->getMessage(),
                    self::LOG_PATH
                );
                continue;
            }
        }

        $this->hotaiCoreCommon->writeLog(
            '------End Of Cron-Update-Status-Arrived-----',
            self::LOG_PATH
        );
    }
    
    /**
     * getCheckOrderCollectionList 取得近 3-5 天有 tracking number 的資料
     *
     * @return @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    private function getCheckOrderCollectionList()
    {
        $now = date("Y-m-d H:i:s");
        $to = date('Y-m-d H:i:s', strtotime('-3 days', strtotime($now)));
        $from = date('Y-m-d H:i:s', strtotime('-5 days', strtotime($now)));

        $collection = $this->trackingCollection->create()
                        ->addFieldToFilter(
                            'created_at', array('from' => $from, 'to' => $to)
        );

        return $collection;
    }
}
