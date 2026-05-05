<?php

namespace Branch8\MarketplaceParentOrderRetryCancel\Model\Cron;

use Branch8\MarketPlaceParentOrder\Model\Action\LinkParentOrderWithChild;
use Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord;
use Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord\CollectionFactory;
use Ecpay\General\Model\Actions\AutoProcessInvoice;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Setup\Exception;
use Branch8\MarketplaceParentOrderRetryCancel\Helper\Logger as LoggerInterface;
use Webkul\Mpsplitorder\Model\MpsplitorderFactory;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class CancelParentOrder
{
    const LOG_FOLDER = "MarketplaceParentOrder/Cron/CancelParentOrder";

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
    private QuoteFactory             $quoteFactory;
    private LoggerInterface          $logger;
    private ResourceConnection       $resourceConnection;
    private AutoProcessInvoice       $autoProcessInvoice;
    private ParenOrderManagement     $parentOrderManagement;
    private HotaiCoreCommonHelper    $hotaiCoreCommonHelper;
    private mixed                    $updateOrderStatus;

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
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     * @param AutoProcessInvoice $autoProcessInvoice
     * @param LoggerInterface $logger
     */
    public function __construct(
        LockModel $lockModel,
        CollectionFactory $collectionFactory,
        ParentOrderFactory $parentOrderFactory,
        OrderRepository $orderRepository,
        LinkParentOrderWithChild $linkParentOrderWithChild,
        QuoteFactory $quoteFactory,
        MpsplitorderFactory $mpsplitorderFactory,
        ResourceConnection $resourceConnection,
        ParenOrderManagement $parenOrderManagement,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        AutoProcessInvoice $autoProcessInvoice,
        LoggerInterface $logger
    ) {
        $this->mpsplitorderFactory      = $mpsplitorderFactory;
        $this->parentOrderFactory       = $parentOrderFactory;
        $this->collectionFactory        = $collectionFactory;
        $this->lockedModel              = $lockModel;
        $this->linkParentOrderWithChild = $linkParentOrderWithChild;
        $this->quoteFactory             = $quoteFactory;
        $this->orderRepository          = $orderRepository;
        $this->logger                   = $logger;
        $this->resourceConnection       = $resourceConnection;
        $this->autoProcessInvoice       = $autoProcessInvoice;
        $this->parentOrderManagement    = $parenOrderManagement;
        $this->hotaiCoreCommonHelper    = $hotaiCoreCommonHelper;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function process()
    {
        $this->lock();
        /**
         * @var $collection \Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord\Collection
         */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', FailedRecord::STATUS_WAITING_INVOICE)
            ->setPageSize(30)->addOrder('entity_id', 'ASC');
        /**
         * @var $record \Branch8\MarketplaceParentOrderRetryCancel\Model\FailedRecord
         */
        foreach ($collection as $record) {
            try {
                $subOrderIds = (string) $record->getData('sub_order_ids');
                if (empty($subOrderIds) || !$subOrders = $this->getSubOrders(explode(',', $subOrderIds))) {
                    $record->setData('status', FailedRecord::STATUS_FAILED_CANCEL);
                    continue;
                }
                $parentOrder = $this->parentOrderFactory->create()->load($record->getData('new_parent_order_id'));
                if (!$parentOrder->getId()) {
                    $record->setData('status', FailedRecord::STATUS_FAILED_CANCEL);
                    continue;
                }
                // if (!$this->isAllSubOrdersHasInvoice($subOrders)) {
                //     continue;
                // }
                // if ($this->cancelParentOrder($parentOrder)) {
                //     $record->setData('status', FailedRecord::STATUS_DONE);
                // } else {
                //     $record->setData('status', FailedRecord::STATUS_FAILED_RECREATE);
                // }

                // we use CleanExpiredOrders to cancel order now,
                // so sub-orders should not have invoices,
                // otherwise we should use return/refund process.
                if ($this->ifAnySubOrdersHasInvoice($subOrders)) {
                    $this->hotaiCoreCommonHelper->writeLog(
                        json_encode([
                            'title'       => 'CancelParentOrder: Some sub-orders have invoices',
                            'message'     => 'Some sub-orders have invoices',
                            'record'      => $record->getData(),
                            'subOrderIds' => $subOrderIds,
                        ]),
                        self::LOG_FOLDER
                    );
                    $record->setData('status', FailedRecord::STATUS_FAILED_CANCEL);
                    continue;
                }

                // change state/status for CleanExpiredOrders cron to cancel these sub-orders.
                // app/code/Branch8/HotaiCancelOrder/Model/CronJob/CleanExpiredOrders.php
                foreach (explode(',', $subOrderIds) as $subOrderId) {
                    /** @var \Magento\Sales\Model\Order $subOrder */
                    $subOrder = $this->orderRepository->get($subOrderId);
                    foreach ($subOrder->getAllVisibleItems() as $item) {
                        $item->setFlowStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE);
                        $item->save();
                    }

                    if ($subOrder->getEntityId()) {
                        $subOrder->setState(\Branch8\HotaiCore\Model\Order\State::STATE_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE);
                        $subOrder->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE);
                    }

                    $subOrder->addStatusHistoryComment(
                        'RecreateParentOrder cron change state/status to cancel_pending_for_parent_order_recreate.'
                    );

                    $this->orderRepository->save($subOrder);
                }

                $parentOrderDetail = $parentOrder->getDetail();
                $parentOrderDetail->setState(\Branch8\HotaiCore\Model\Order\State::STATE_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE);
                $parentOrderDetail->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE);
                $parentOrderDetail->save();

                $record->setData('status', FailedRecord::STATUS_DONE);

                $this->hotaiCoreCommonHelper->writeLog(
                    json_encode([
                        'title'       => 'CancelParentOrder: Success',
                        'message'     => '',
                        'record'      => $record->getData(),
                        'subOrderIds' => $subOrderIds,
                    ]),
                    self::LOG_FOLDER
                );
            } catch (\Exception $exception) {
                $this->hotaiCoreCommonHelper->writeLog(
                    json_encode([
                        'title'       => 'CancelParentOrder: Exception',
                        'message'     => $exception->getMessage(),
                        'record'      => $record->getData(),
                        'subOrderIds' => $subOrderIds,
                    ]),
                    self::LOG_FOLDER
                );
                $record->setData('status', FailedRecord::STATUS_FAILED_CANCEL);
            }
        }
        $collection->save();
        $this->unlock();
    }

    /**
     * @param ParentOrder $parentOrder
     * @return bool
     */
    private function cancelParentOrder(ParentOrder $parentOrder)
    {
        try {
            $this->parentOrderManagement->cancel($parentOrder);
            return true;
        } catch (\Exception $exception) {
            $this->logger->info("Error cancel parent order:");
            $this->logger->critical($exception->getMessage());
            $this->logger->info($exception->getTraceAsString());
            $this->logger->info('Parent order id: ' . $parentOrder->getId());
            return false;
        }
    }

    /**
     * @param array $suborders
     * @return bool
     */
    private function isAllSubOrdersHasInvoice(array $suborders)
    {
        /**
         * @var $suborder \Magento\Sales\Model\Order
         */
        $allHaveInvoices = true;
        foreach ($suborders as $suborder) {
            if (count($suborder->getInvoiceCollection()) == 0) {
                $allHaveInvoices = false;
                break;
            }
        }
        return $allHaveInvoices;
    }

    private function ifAnySubOrdersHasInvoice(array $suborders)
    {
        /**
         * @var $suborder \Magento\Sales\Model\Order
         */
        $anyHaveInvoices = false;
        foreach ($suborders as $suborder) {
            if (count($suborder->getInvoiceCollection()) > 0) {
                $anyHaveInvoices = true;
                break;
            }
        }
        return $anyHaveInvoices;
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
                $order                      = $this->orderRepository->get($id);
                $instances[$order->getId()] = $order;
            } catch (NoSuchEntityException $exception) {
                continue;
            }
        }
        return $instances;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function lock()
    {
        if ($this->lockedModel->isQueueLocked(LockModel::CANCEL_PROCESS_FLAG)) {
            throw new Exception(
                __('Another lock detected (
            the process unread reminder queue is in a progress).')->render()
            );
        }
        $this->lockedModel->setIsQueueLocked(LockModel::CANCEL_PROCESS_FLAG, true);
    }

    /**
     * @return void
     */
    protected function unlock()
    {
        $this->lockedModel->setIsQueueLocked(LockModel::CANCEL_PROCESS_FLAG, false);
    }

    /**
     * @return void
     */
    public function forceUnlock()
    {
        $this->lockedModel->setIsQueueLocked(LockModel::CANCEL_PROCESS_FLAG, false);
    }

}
