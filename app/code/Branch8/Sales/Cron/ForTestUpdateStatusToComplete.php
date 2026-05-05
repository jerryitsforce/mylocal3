<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Model\Order\State as HotaiState;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;

class ForTestUpdateStatusToComplete
{

    const LOG_PATH = 'cron/ForTestupdateStatusToComplete/';
    /** @var \Branch8\Sales\Logger\Logger $logger */
    private $logger;
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;
    /** @var \Magento\Sales\Model\OrderRepository $orderRepository */
    protected $orderRepository;
    /** @var \Magento\Sales\Model\Order $order */
    protected $order;
    /** @var \Magento\Framework\App\State $state */
    protected $state;
    /** @var \Branch8\Sales\Helper\Logger $loggerhelper */
    protected $loggerhelper;
    /** @var \Magento\Sales\Api\Data\OrderInterface $orderInterface */
    protected $orderInterface;
    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipment */
    protected $shipment;
    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;
    /** @var  \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory */
    protected $trackingCollection;

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
        TrackCollectionFactory $collectionFactory
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
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->logger = $this->loggerhelper->setPath($this->logger, self::LOG_PATH);

        $this->logger->info("------Start Of Cron-Update-Status-Complete-----");

        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getItems() as $data) {
            try {

                $shipment = $data->getShipment();

                foreach ($shipment->getItemsCollection() as $item) {
                    $item = $item->getOrderItem();

                    if ($item->getFlowStatus() == Status::STATUS_COMPLETE) {
                        continue;
                    }

                    if ($item->getFlowStatus() == Status::STATUS_FAILED_DELIVERY) {
                        continue;
                    }

                    if ($item->getRmaStatus() != \Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE) {
                        continue;
                    }


                    $itemId = $item->getId();
                    
                    $this->logger->info("------Order Item Entity Id: $itemId-----");
                    $order = $item->getOrder();
                    $item->setFlowStatus(Status::STATUS_COMPLETE);
                    $item->save();
                    $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, Status::STATUS_COMPLETE);
                    $this->logger->info("------Update Status to Complete-----");
                }

            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                continue;
            }
        }

        $this->logger->info("------End Of Cron-Update-Status-Arrived-----");
    }

    /**
     * getCheckOrderCollectionList 開立 tracking number 後 30-33 天的單 (35天是怕漏掉)
     *
     * @return @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    private function getCheckOrderCollectionList()
    {
        $now = date("Y-m-d H:i:s");
        $to = date('Y-m-d H:i:s', strtotime('-30 days', strtotime($now)));
        $from = date('Y-m-d H:i:s', strtotime('-10 days', strtotime($now)));

        $collection = $this->trackingCollection->create()
                        ->addFieldToFilter(
                            'created_at', array('from' => $from, 'to' => $now)
        );

        return $collection;
    }
}
