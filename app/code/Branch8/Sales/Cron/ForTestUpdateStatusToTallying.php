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
use Magento\Sales\Model\OrderFactory;
use \Branch8\Shipping\Model\ShippingMethod;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;


class ForTestUpdateStatusToTallying
{

    const LOG_PATH = 'cron/ForTestupdateStatusToTallying/';
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

    protected $timezoneInterface;

    public function __construct(
        Logger $logger,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        OrderFactory $order,
        State $state,
        LoggerHelper $loggerhelper,
        OrderInterface $orderInterface,
        UpdateOrderStatus $updateOrderStatus,
        TimezoneInterface $timezoneInterface
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->orderInterface = $orderInterface;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->logger = $this->loggerhelper->setPath($this->logger, self::LOG_PATH);
        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getData() as $data) {
            try {

                $entityId = $data['entity_id'];

                $this->logger->info("------Order Entity Id: $entityId-----");

                $order = $this->orderInterface->load($entityId);
                $order->setIsForceUpdateStatus(true);
                $order->setStatus(Status::STATUS_TALLYING);
                $order->addStatusHistoryComment(__('For Test Cron Update Order Status To %1', Status::STATUS_TALLYING));
                $this->orderRepository->save($order);

                foreach ($order->getAllVisibleItems() as $item) {
                    if ($item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                        continue;
                    }

                    if ($item->getFlowStatus() != Status::STATUS_PROCESSING) {
                        continue;
                    }
                    $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE);
                    $item->setFlowStatus(Status::STATUS_TALLYING);
                    $item->save();

                    $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, Status::STATUS_TALLYING);
                }

                $this->logger->info("------Update Status to TALLYING-----");

            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                continue;
            }
        }

        $this->logger->info("------End Of Cron-Update-Status-TALLYING-----");
    }

    /**
     * getCheckOrderCollectionList 取得需要變更狀態的訂單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    private function getCheckOrderCollectionList()
    {
        
        $yesterday = date('Y-m-d 16:00:00', strtotime('-10 day'));
        $to = date('Y-m-d H:i:s');
        
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter(
                'status', Status::STATUS_PROCESSING)
            ->addFieldToFilter(
                'created_at', array('from' => $yesterday, 'to' => $to))
            ->addFieldToFilter(
                'shipping_method', [
                    'in' =>
                    [
                        ShippingMethod::METHOD_HOME,
                        ShippingMethod::METHOD_CONVENIENCE_STORE,
                    ],
                ]
            );

        return $collection;
    }
}
