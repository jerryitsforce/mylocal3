<?php

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\Actions;

use Branch8\HelpDesk\Model\Order;
use Branch8\HotaiCore\Model\Order\State as HotailOrderState;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;
use Branch8\Marketplace\Model\Actions\CancelOrderAction;
use Branch8\Marketplace\Model\Actions\GetSellerByOrder;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\LocalizedException;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;

class ParentOrderCancelAction
{
    /**
     * @var
     */
    private $parentOrderRepository;
    /**
     * @var
     */
    private $parentOrderManagement;
    /**
     * @var CancelOrderAction
     */
    private CancelOrderAction $cancelOrderAction;
    /**
     * @var GetSellerByOrder
     */
    private GetSellerByOrder $getSellerByOrder;
    private LoggerInterface $logger;
    private Manager $eventManager;

    /**
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param CancelOrderAction $cancelOrderAction
     * @param GetSellerByOrder $getSellerByOrder
     * @param Manager $eventManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement,
        CancelOrderAction              $cancelOrderAction,
        GetSellerByOrder               $getSellerByOrder,
        Manager                        $eventManager,
        LoggerInterface                $logger
    )
    {
        $this->getSellerByOrder = $getSellerByOrder;
        $this->cancelOrderAction = $cancelOrderAction;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }

    /**
     * @param ParentOrder $parentOrder
     * @param DataObject|null $cancelDetail
     * @return true
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(ParentOrder $parentOrder, DataObject $cancelDetail = null)
    {
        try {
            /**
             * @var $subOrder Order
             */

            $isPaid = false;
            foreach ($parentOrder->getSubOrders() as $subOrder) {
                // if (!$subOrder->canCancel()) {
                //     throw new LocalizedException(__('Can not cancel suborder %1', $subOrder->getIncrementId()));
                // }
                if(!$subOrder->getData('is_paid')) {
                    $this->cancelSubOrder($subOrder, $cancelDetail);
                } else{
                    $this->eventManager->dispatch(
                        'sales_order_cancel_paied_order',
                        ['order' => $subOrder]
                    );
                }

                $isPaid = $subOrder->getData('is_paid');

            }

            $state = HotailOrderState::STATE_CANCELED;
            $status = HotaiOrderStatus::STATUS_CANCELED;

            if ($parentOrder->getDetail()->getEcpayInvoiceCustomerIdentifier() && $isPaid) {
                $state = HotailOrderState::STATE_CANCEL_PENDING;
                $status =  HotaiOrderStatus::STATUS_CANCEL_PENDING;
            }

            if ($cancelDetail) {
                $reason = $cancelDetail->getData('reason');
                $reasonDescription = $cancelDetail->getData('reason_description');
                $comment = $reason . ': ' . $reasonDescription;
                $this->parentOrderManagement->saveStatusHistory(
                    $parentOrder,
                    $comment,
                    $status,
                    false,
                    true
                );
            }



            $parentOrder
                ->getDetail()
                ->setState($state)
                ->setStatus($status)
                ->save();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->info($e->getTraceAsString());
            throw $e;
        }
        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param DataObject $cancelDetail
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function cancelSubOrder(\Magento\Sales\Model\Order $order, DataObject $cancelDetail = null)
    {
        try {
            $seller = $this->getSellerByOrder->execute($order);
            $sellerId = $seller ? $seller->getId() : null;
            $this->cancelOrderAction
                ->beginTransaction()
                ->execute($order, $sellerId)
                ->saveCancelReason($order, $cancelDetail)
                ->updateTrackingCollection($order, $sellerId)
                ->updateOrderItemFlowStatus($order, $status = Status::STATUS_CANCELED)
                ->updateSellerOrderStatus($order, $sellerId)
                ->commitTransation();
            $this->eventManager->dispatch(
                'mp_order_cancel_after',
                ['seller_id' => $sellerId, 'order' => $order]
            );
        } catch (\Exception $exception) {
            $this->cancelOrderAction->rollBack();
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }
}
