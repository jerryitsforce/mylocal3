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
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;

class UpdateFlagshipFakeOrderStatus
{

    const LOG_PATH = 'Sales/Cron/UpdateFlagshipFakeOrderStatus';
    
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

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    public function __construct(
        Logger $logger,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        OrderFactory $order,
        State $state,
        LoggerHelper $loggerhelper,
        OrderInterface $orderInterface,
        UpdateOrderStatus $updateOrderStatus,
        HotaiCoreCommon $hotaiCoreCommon
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->orderInterface = $orderInterface;
        $this->updateOrderStatus = $updateOrderStatus;
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
            "------Start Of Cron-Update-Flagship-FakeOrder-Status-----",
            self::LOG_PATH
        );
    
        $collection = $this->getCheckOrderCollectionList();

        foreach ($collection->getData() as $data) {
            try {

                $entityId = $data['entity_id'];

                $this->hotaiCoreCommon->writeLog(
                    "------Order Entity Id: $entityId-----",
                    self::LOG_PATH
                );

                $order = $this->orderInterface->load($entityId);

                foreach ($order->getAllVisibleItems() as $item) {
                    if ($item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                        continue;
                    }

                    $item->setFlowStatus(Status::STATUS_ARRIVED);
                    $item->save();
                }

                $this->hotaiCoreCommon->writeLog(
                    "------Update Flagship FakeOrder Item To Arrived ----",
                    self::LOG_PATH
                );

                $order->setStatus(Status::STATUS_ARRIVED);
                $order->addStatusHistoryComment(__('Cron Update Flagship Fake Order Status To %1', Status::STATUS_TALLYING));
                $this->orderRepository->save($order);

                $this->hotaiCoreCommon->writeLog(
                        "-----Update Order Status to Arrived-----",
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

        $this->hotaiCoreCommon->writeLog(
            "-----Cron-Update-Flagship-FakeOrder-Status----",
            self::LOG_PATH
        );
    }

    /**
     * getCheckOrderCollectionList 取得需要變更狀態的訂單
     *
     * @return  \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     */
    private function getCheckOrderCollectionList()
    {
        
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('is_virtual', 1)
            ->addFieldToFilter('ecpay_invoice_tag', 1)
            ->addFieldToFilter('status', Status::STATUS_PROCESSING)
            ->addFieldToFilter(
                'is_flagship_store_process_order', ['neq' => 'NULL']);

        return $collection;
    }
}
