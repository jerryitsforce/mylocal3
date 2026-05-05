<?php

namespace Branch8\MarketplaceParentOrderRetryCancel\Model\Cron;

use Branch8\MarketPlaceParentOrder\Model\Action\LinkParentOrderWithChild;
use Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord;
use Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord\CollectionFactory;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Ecpay\General\Model\Actions\AutoProcessInvoice;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Setup\Exception;
use Branch8\MarketplaceParentOrderRetryCancel\Helper\Logger as LoggerInterface;
use Webkul\Mpsplitorder\Model\MpsplitorderFactory;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class RecreateParentOrder
{
    const LOG_FOLDER_NAME = 'MarketplaceParentOrder/Cron/RecreateParentOrder';

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;
    /**
     * @var \Branch8\MarketplaceParentOrderRetryCancel\Model\Cron\LockModel
     */
    private \Branch8\MarketplaceParentOrderRetryCancel\Model\Cron\LockModel $lockedModel;

    private ParentOrderFactory $parentOrderFactory;

    private OrderRepository $orderRepository;

    private MpsplitorderFactory $mpsplitorderFactory;

    private LinkParentOrderWithChild $linkParentOrderWithChild;
    private QuoteFactory $quoteFactory;
    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;
    private AutoProcessInvoice $autoProcessInvoice;
    private ParenOrderManagement $parentOrderManagement;
    private mixed $updateOrderStatus;
    private HotaiCoreCommonHelper $hotaiCoreCommonHelper;

    /**
     * @param \Branch8\MarketplaceParentOrderRetryCancel\Model\Cron\LockModel $lockModel
     * @param CollectionFactory $collectionFactory
     * @param ParentOrderFactory $parentOrderFactory
     * @param OrderRepository $orderRepository
     * @param LinkParentOrderWithChild $linkParentOrderWithChild
     * @param QuoteFactory $quoteFactory
     * @param MpsplitorderFactory $mpsplitorderFactory
     * @param ResourceConnection $resourceConnection
     * @param ParenOrderManagement $parenOrderManagement
     * @param AutoProcessInvoice $autoProcessInvoice
     * @param LoggerInterface $logger
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     */
    public function __construct(
        LockModel                $lockModel,
        CollectionFactory        $collectionFactory,
        ParentOrderFactory       $parentOrderFactory,
        OrderRepository          $orderRepository,
        LinkParentOrderWithChild $linkParentOrderWithChild,
        QuoteFactory             $quoteFactory,
        MpsplitorderFactory      $mpsplitorderFactory,
        ResourceConnection       $resourceConnection,
        ParenOrderManagement     $parenOrderManagement,
        AutoProcessInvoice       $autoProcessInvoice,
        LoggerInterface          $logger,
        HotaiCoreCommonHelper    $hotaiCoreCommonHelper
    )
    {
        $this->mpsplitorderFactory = $mpsplitorderFactory;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->lockedModel = $lockModel;
        $this->linkParentOrderWithChild = $linkParentOrderWithChild;
        $this->quoteFactory = $quoteFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->autoProcessInvoice = $autoProcessInvoice;
        $this->parentOrderManagement = $parenOrderManagement;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function process()
    {
        $this->forceUnlock();
        $this->lock();
        /**
         * @var $collection \Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord\Collection
         */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', FailedRecord::STATUS_PENDING)
            ->setPageSize(30)->addOrder('entity_id', 'ASC');

        /**
         * @var $record \Branch8\MarketplaceParentOrderRetryCancel\Model\FailedRecord
         */
        foreach ($collection as $record) {
            try {
                //1.re-create parent order
                $record->setData('status', FailedRecord::STATUS_PROCESSING);
                $subOrderIds = (string)$record->getData('sub_order_ids');
                if (empty($subOrderIds) || !$instances = $this->getSubOrders(explode(',', $subOrderIds))) {
                    $record->setData('status', FailedRecord::STATUS_FAILED_RECREATE);
                    continue;
                }
                $lastItem = end($instances);
                $parentOrder = $this->mpsplitorderFactory->create(
                    [
                        'data' => [
                            'hotai_reserved_order_id' => $record->getData('old_parent_order_id'),
                            'order_ids' => $subOrderIds,
                            'master_quote_id' => $record->getData('master_quote_id'),
                            'last_order_id' => $lastItem->getId(),
                        ]
                    ]
                );
                $parentOrder->setDataChanges('true')->getResource()->save($parentOrder);
                $parentOrder->setDesireStatus('processing');
                $this->linkParentOrderWithChild->execute($parentOrder,
                    null,
                    array_unique(array_keys($instances)),
                    false,
                    false
                );
                //2. invoice all suborders
                // $parentOrder = $this->parentOrderFactory->create()->load($parentOrder->getData('index_id'));
                // $this->invoiceParentOrders($parentOrder);
                //3. Cancel parent order
                // $this->cancelParentOrder($parentOrder);

                /// Wait chad's cron create invoice for all sub-orders
                $record->setData('new_parent_order_id', $parentOrder->getData('index_id'));
                $record->setData('status', FailedRecord::STATUS_WAITING_INVOICE);
            } catch (\Exception $exception) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Title" => "RecreateParentOrder: Error when recreating parent order",
                    "Exception Message" => $exception->getMessage(),
                    "Record Data" => $record->getData(),
                    "Exception Trace" => $exception->getTraceAsString(),
                ]), self::LOG_FOLDER_NAME);
            }
        }
        $collection->save();
        $this->unlock();
    }

    /**
     * @return mixed
     */
    public function getUpdateOrderStatus()
    {
        if ($this->updateOrderStatus === null) {
            $this->updateOrderStatus = ObjectManager::getInstance()->get(UpdateOrderStatus::class);
        }
        return $this->updateOrderStatus;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return void
     */
    private function cancelParentOrder(ParentOrder $parentOrder)
    {
        try {
            $this->parentOrderManagement->cancel($parentOrder);
        } catch (\Exception $exception) {
            $this->logger->info("Error cancel parent order:");
            $this->logger->critical($exception->getMessage());
            $this->logger->info($exception->getTraceAsString());
            $this->logger->info('Parent order id: ' . $parentOrder->getId());
        }
    }

    /**
     * @param $parentId
     * @return void
     */
    private function invoiceParentOrders(ParentOrder $parentOrder)
    {
        /**
         * @var $parentOrder \Branch8\MarketPlaceParentOrder\Model\ParentOrder
         */

        if ($parentOrder->getId() && $subOrders = $parentOrder->getSubOrders()) {
            /**
             * @var $subOrder \Magento\Sales\Model\Order
             */
            foreach ($subOrders as $subOrder) {
                try {
                    $this->autoProcessInvoice->execute((int)$subOrder->getId());
                } catch (\Exception $exception) {
                    $this->logger->info("Error when auto process invoice :");
                    $this->logger->critical($exception->getMessage());
                    $this->logger->info($exception->getTraceAsString());
                    $this->logger->info('Parent order id: ' . $parentOrder->getId());
                }
            }
        }
    }

    /**
     * @param $ids
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getSubOrders($ids)
    {
        $instances = [];
        foreach ($ids as $id) {
            try {
                if (!$this->checkIsLinkedWithAnother($id)) {
                    $order = $this->orderRepository->get($id);
                    $instances[$order->getId()] = $order;
                }
            } catch (NoSuchEntityException $exception) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Title" => "RecreateParentOrder: Error when getting sub orders",
                    "Exception Message" => $exception->getMessage(),
                    "Sub Order ID" => $id,
                    "Exception Trace" => $exception->getTraceAsString(),
                ]), self::LOG_FOLDER_NAME);

                continue;
            }
        }
        return $instances;
    }

    /**
     * @param $subOrderId
     * @return mixed
     */
    private function checkIsLinkedWithAnother($subOrderId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from('sales_parent_order_children')->where('children_id = ?', $subOrderId);
        return $connection->fetchRow($select);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function lock()
    {
        if ($this->lockedModel->isQueueLocked(LockModel::RECREATE_PROCESS_FLAG)) {
            throw new Exception(__('Another lock detected (
            the process unread reminder queue is in a progress).')->render()
            );
        }
        $this->lockedModel->setIsQueueLocked(LockModel::RECREATE_PROCESS_FLAG, true);
    }

    /**
     * @return void
     */
    protected function unlock()
    {
        $this->lockedModel->setIsQueueLocked(LockModel::RECREATE_PROCESS_FLAG, false);
    }

    /**
     * @return void
     */
    public function forceUnlock()
    {
        $this->lockedModel->setIsQueueLocked(LockModel::RECREATE_PROCESS_FLAG, false);
    }

}
