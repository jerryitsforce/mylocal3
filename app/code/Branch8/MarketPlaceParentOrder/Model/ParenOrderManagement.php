<?php
declare (strict_types = 1);

namespace Branch8\MarketPlaceParentOrder\Model;

use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel\Notifier;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderComment\Sender;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderConfirmation\Notifier as ConfirmationNotifier;
use Branch8\MarketPlaceParentOrder\Model\Services\CalculateTotalParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\ReorderHelper;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Event\Manager;

class ParenOrderManagement implements ParentOrderManagementInterface
{
    /**
     * @var ResourceModel\ParentOrderDetail
     */
    private $detailResource;
    /***
     * @var Order\Config
     */
    private Order\Config $config;
    /**
     * @var \Magento\Payment\Helper\Data
     */
    private \Magento\Payment\Helper\Data $paymentData;
    /**
     * @var LoggerInterface
     */
    private Log $log;
    /**
     * @var CalculateTotalParentOrder
     */
    private CalculateTotalParentOrder $calculateTotalParentOrder;
    /**
     * @var
     */
    private $totals = [];

    private Notifier $notifier;
    /**
     * @var ResourceModel\ParentOrder
     */
    private ResourceModel\ParentOrder $parentOrderResource;

    private OrderRepository $orderRepository;

    private ReorderHelper $reorderHelper;
    /**
     * @var UpdateOrderStatus
     */
    protected $updateOrderStatus = null;
    /**
     * @var
     */
    protected $comment;
    private HistoryFactory $historyFactory;
    private Sender $commentSender;
    private ResourceConnection $resourceConnection;

    private $confirmationNotifier;

    private Manager $_eventManager;

    /**
     * @param ResourceModel\ParentOrderDetail $detailResource
     * @param OrderRepository $orderRepository
     * @param ResourceModel\ParentOrder $parentOrderResource
     * @param Order\Config $config
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param CalculateTotalParentOrder $calculateTotalParentOrder
     * @param Notifier $notifier
     * @param ConfirmationNotifier $confirmationNotifier
     * @param ReorderHelper $reorderHelper
     * @param HistoryFactory $historyFactory
     * @param Log $log
     * @param Sender $commentSender
     * @param ResourceConnection $resourceConnection
     * @param Manager $eventManager
     */
    public function __construct(
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail $detailResource,
        OrderRepository $orderRepository,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder $parentOrderResource,
        \Magento\Sales\Model\Order\Config $config,
        \Magento\Payment\Helper\Data $paymentData,
        CalculateTotalParentOrder $calculateTotalParentOrder,
        Notifier $notifier,
        ConfirmationNotifier $confirmationNotifier,
        ReorderHelper $reorderHelper,
        HistoryFactory $historyFactory,
        Log $log,
        Sender $commentSender,
        ResourceConnection $resourceConnection,
        Manager              $eventManager
    ) {
        $this->parentOrderResource = $parentOrderResource;
        $this->notifier = $notifier;
        $this->config = $config;
        $this->detailResource = $detailResource;
        $this->paymentData = $paymentData;
        $this->log = $log;
        $this->orderRepository = $orderRepository;
        $this->confirmationNotifier = $confirmationNotifier;
        $this->calculateTotalParentOrder = $calculateTotalParentOrder;
        $this->reorderHelper = $reorderHelper;
        $this->historyFactory = $historyFactory;
        $this->commentSender = $commentSender;
        $this->resourceConnection = $resourceConnection;
        $this->_eventManager = $eventManager;
    }

    /**
     * @return void
     */

    public function getUpdateOrderStatus()
    {
        if($this->updateOrderStatus === null) {
            $this->updateOrderStatus=ObjectManager::getInstance()->get(UpdateOrderStatus::class);
        }
        return $this->updateOrderStatus;
    }
    /**
     * @param ParentOrderInterface $parentOrder
     * @return true
     * @throws LocalizedException
     */
    public function hold(ParentOrderInterface $parentOrder)
    {
        $subOrders = $parentOrder->getSubOrders();
        $notSubOrderExceptions = new LocalizedException(
            __('Parent Order "%1" can not hold : no sub-orders found',
                $parentOrder->getDetail()->getIncrementId()
            )
        );
        $canNotHold = [];
        if (empty($subOrders)) {
            throw $notSubOrderExceptions;
        }
        /**
         * @var $order Order
         */
        foreach ($subOrders as $order) {
            if (!$order->canHold()) {
                $canNotHold[] = $order->getIncrementId();
            }
        }
        if (!empty($canNotHold)) {
            throw new LocalizedException(
                __('Parent Order "%1" can not hold : sub-orders %2 can not hold',
                    $parentOrder->getDetail()->getIncrementId(), join(',', $canNotHold)
                )
            );
        }
        foreach ($subOrders as $order) {
            try {
                $order->hold();
                $this->orderRepository->save($order);
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
        $detail = $parentOrder->getDetail();
        $this->detailResource->save(
            $parentOrder->getDetail()
                ->setHoldBeforeState($detail->getState())
                ->setHoldBeforeStatus($detail->getStatus())
                ->setState(Order::STATE_HOLDED)
                ->setStatus($this->config->getStateDefaultStatus(Order::STATE_HOLDED))

        );
        return true;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canHold(ParentOrderInterface $parentOrder)
    {
        $notHoldableStates = [
            Order::STATE_CANCELED,
            Order::STATE_PAYMENT_REVIEW,
            Order::STATE_COMPLETE,
            Order::STATE_CLOSED,
            Order::STATE_HOLDED,
        ];
        if (in_array($parentOrder->getDetail()->getState(), $notHoldableStates)) {
            return false;
        }
        $subOrders = $parentOrder->getSubOrders();
        if (empty($subOrders)) {
            return false;
        }
        /**
         * @var $order Order
         */
        foreach ($subOrders as $order) {
            if (!$order->canHold()) {
                $canNotHold[] = $order->getIncrementId();
            }
        }
        return empty($canNotHold);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canUnhold(ParentOrderInterface $parentOrder)
    {
        if ($this->isPaymentReview($parentOrder)) {
            return false;
        }
        $subOrders = $parentOrder->getSubOrders();
        if (empty($subOrders)) {
            return false;
        }
        /**
         * @var $order Order
         */
        foreach ($subOrders as $order) {
            if (!$order->canUnhold()) {
                $canNotUnHold[] = $order->getIncrementId();
            }
        }

        return empty($canNotUnHold) && $parentOrder->getDetail()->getState() === Order::STATE_HOLDED;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return $this
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function unhold(ParentOrderInterface $parentOrder)
    {
        $subOrders = $parentOrder->getSubOrders();
        if (!$this->canUnhold($parentOrder) || empty($subOrders)) {
            throw new LocalizedException(__('You cannot remove the hold.'));
        }

        foreach ($subOrders as $order) {
            try {
                $order->unHold();
                $result = (bool) $this->orderRepository->save($order);
                if (empty($result)) {
                    throw new LocalizedException(__('Can not unhold sub-order:', $order->getIncrementId()));
                }
            } catch (\Exception $exception) {
                throw $exception;
            }
        }

        $detail = $parentOrder->getDetail();
        $detail->setState($parentOrder->getDetail()->getHoldBeforeState())
            ->setStatus($parentOrder->getDetail()->getHoldBeforeStatus());
        $detail->setHoldBeforeState(null);
        $detail->setHoldBeforeStatus(null);
        $this->detailResource->save($detail);
        return $this;
    }

    /***
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */

    public function isPaymentReview(ParentOrderInterface $parentOrder)
    {
        return $parentOrder->getDetail()->getState() === Order::STATE_PAYMENT_REVIEW;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return true
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function cancel(ParentOrderInterface $parentOrder)
    {
        $subOrders = $parentOrder->getSubOrders();
        $notSubOrderExceptions = new LocalizedException(
            __('Parent Order "%1" can not cancel : no sub-orders found',
                $parentOrder->getDetail()->getIncrementId()
            )
        );

        if (empty($subOrders)) {
            throw $notSubOrderExceptions;
        }
        // /**
        //  * @var $order Order
        //  */
        // foreach ($subOrders as $order) {
        //     if (!$order->canCancel()) {
        //         $canCancelIds[] = $order->getIncrementId();
        //     }
        // }
        // if (!empty($canCancelIds)) {
        //     throw new LocalizedException(
        //         __('Parent Order "%1" can not cancel : sub-orders %2 can not cancel',
        //             $parentOrder->getDetail()->getIncrementId(), join(',', $canCancelIds)
        //         )
        //     );
        // }

        $isPaid = 0;
        foreach ($subOrders as $order) {
            try {

                /**
                 *  If not paid, then cancel
                 */
                if(!$order->getData('is_paid')) {
                    $order->cancel();
                    $this->orderRepository->save($order);
                } else {
                    $this->_eventManager->dispatch(
                        'sales_order_cancel_paied_order',
                        ['order' => $order]
                    );
                }

                $this->updateSubOrderStatus(
                    $order,
                    Status::STATUS_CANCELED
                );

                $isPaid = $order->getData('is_paid');
            } catch (\Exception $exception) {
                throw $exception;
            }
        }

        $state = State::STATE_CANCELED;
        $status = Status::STATUS_CANCELED;
        $isSentEmail = true;
        if ($parentOrder->getDetail()->getEcpayInvoiceCustomerIdentifier() && $isPaid) {
            $state = State::STATE_CANCEL_PENDING;
            $status =  Status::STATUS_CANCEL_PENDING;
            $isSentEmail = false;
        }

        $this->detailResource->save(
            $parentOrder
                ->getDetail()
                ->setState($state)
                ->setStatus($status)
        );
        try {
            if($isSentEmail){
                $this->sendCancelEmail($parentOrder);
            }
        } catch (\Throwable $exception) {
            $this->log->logException('OrderManagement', $exception, ['action' => 'cancel']);
        }
        return true;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canCancel(ParentOrderInterface $parentOrder)
    {

        $subOrders = $parentOrder->getSubOrders();
        if (empty($subOrders)) {
            return false;
        }

        return true;

        /**
         * Do not check cancel availability bc it's using ori M2 flow
         */
        // /**
        //  * @var $order Order
        //  */
        // foreach ($subOrders as $order) {
        //     if (!$order->canCancel()) {
        //         $canCancel[] = $order->getIncrementId();
        //     }
        // }
        // return empty($canCancel);
    }

    public function sendCancelEmail(ParentOrderInterface $parentOrder)
    {
        try {
            $this->notifier->notify($parentOrder);
        } catch (\Throwable $exception) {
            $this->log->logException('OrderManagement', $exception, ['action' => 'sendCancelEmail']);
        }
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return void
     */
    public function sendConfirmationEmail(ParentOrderInterface $parentOrder)
    {
        try {
            $this->confirmationNotifier->notify($parentOrder);
        } catch (\Throwable $exception) {
            $this->log->logException('OrderManagement', $exception, ['action' => 'sendConfirmationEmail']);
        }
    }

    /**
     * @param ParentOrder $parentOrder
     * @return \Magento\Framework\View\Element\Template|null
     */
    public function getInfoBlock(ParentOrder $parentOrder)
    {
        $info = null;
        try {
            if ($subOrders = $parentOrder->getSubOrders()) {
                /**
                 * @var $firstOrder Order
                 */
                $firstOrder = $subOrders->getFirstItem();
                $payment = $firstOrder->getPayment();
                $info = $this->paymentData->getInfoBlock($payment);
            }
        } catch (\Throwable $exception) {
            $this->log->logException('OrderManagement', $exception, ['action' => 'getInfoBlock']);
        }
        return $info;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return string
     */
    public function getPaymentHtml(ParentOrder $parentOrder)
    {
        $info = '';
        try {
            if ($payments = $parentOrder->getAllPayments()) {
                $payment = current($payments);
                $info = $this->paymentData->getInfoBlockHtml(
                    $payment,
                    $parentOrder->getDetail()->getStoreId()
                );
            }
        } catch (\Throwable $exception) {
            $this->log->logException('OrderManagement', $exception, ['action' => 'getPaymentHtml']);
        }
        return $info;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return array|mixed
     */
    public function getTotals(ParentOrderInterface $parentOrder)
    {
        if (isset($this->totals[$parentOrder->getIndexId()])) {
            return $this->totals[$parentOrder->getIndexId()];
        }

        $totals = $this->calculateTotalParentOrder->total($parentOrder);
        usort($totals, function ($a, $b) {
            if ($a['sort'] === $b['sort']) {
                return 0;
            }
            return $a['sort'] > $b['sort'] ? 1 : -1;
        });
        $this->totals[$parentOrder->getIndexId()] = $totals;
        return $totals;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @param $code
     * @return mixed|null
     */
    public function getTotalByKey(ParentOrderInterface $parentOrder, string $code)
    {
        $totals = $this->getTotals($parentOrder);
        foreach ($totals as $total) {
            if ($total->getCode() === $code) {
                return $total;
            }
        }
        return null;
    }

    /**
     * @param $parentId
     * @param $subOrderIds
     * @return bool
     */
    public function assignSubordersToParentOrder($parentId, $subOrderIds = [])
    {
        if (empty($subOrderIds)) {
            return false;
        }
        $this->parentOrderResource->saveSubOrderRelation(
            (int) $parentId,
            $subOrderIds
        );
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canReorder(ParentOrderInterface $parentOrder)
    {
        return $this->reorderHelper->canReorder($parentOrder);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canEdit(ParentOrderInterface $parentOrder)
    {
        if (!$parentOrder->canEdit()) {
            return false;
        }
        $subOrders = $parentOrder->getSubOrders();
        if (empty($subOrders)) {
            return false;
        }
        /**
         * @var $order Order
         */
        foreach ($subOrders as $order) {
            if (!$order->canEdit()) {
                $cannotEdit[] = $order->getIncrementId();
            }
        }
        return empty($cannotEdit);
    }

    /**
     * @param Order $order
     * @param $status
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateSubOrderStatus(Order $order, $status, $comment = '')
    {
        try {
            $order->setStatus($status);
            if ($status == Status::STATUS_CANCELED) {
                $order->setState($status);

                if ($order->getData('is_paid') && $order->getEcpayInvoiceCustomerIdentifier()) {
                    $order->setState(State::STATE_CANCEL_PENDING);
                    $order->setStatus(Status::STATUS_CANCEL_PENDING);
                    $status = Status::STATUS_CANCEL_PENDING;
                }

            }

            $statusComment = __("Update Status to %1.", $status);
            if ($comment) {
                $statusComment = $statusComment . __($comment);
            } elseif ($this->comment) {
                $statusComment = $statusComment . __($this->comment);
            }
            $order->addCommentToStatusHistory($statusComment);
            $this->orderRepository->save($order);
            $this->updateItemFlowStatus($order, $status);
        } catch (\Exception $exception) {
            throw $exception;
        }

    }

    /**
     * updateItemFlowStatus
     *
     * @param mixed $order
     * @param string $status
     * @return void
     */
    public function updateItemFlowStatus($order, $status)
    {
        foreach ($order->getItemsCollection() as $item) {

            if ($status == Status::STATUS_CANCEL_PENDING) {
                $status = Status::STATUS_FINANCIAL_REVIEW;
            }

            $this->getUpdateOrderStatus()->updateItemStatusById(
                $item->getItemId(),
                $status,
                $order->getEntityId()
            );

            $item->setFlowStatus($status);
            $item->save();
        }
    }

    /**
     * setComment
     *
     * @param string $comment
     * @return $this->comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @param string $comment
     * @param string|null $status
     * @param bool $notifyCustomer
     * @param bool $isVisibleOnFront
     * @return $this|mixed
     * @throws \Exception
     */
    public function saveStatusHistory(
        ParentOrderInterface $parentOrder,
        string $comment,
        string $status = null,
        bool $notifyCustomer = false,
        bool $isVisibleOnFront = false
    ) {
        $status = $status ? $status : $parentOrder->getDetail()->getStatus();
        $history = $this->historyFactory->create();
        $history->setParentId($parentOrder->getIndexId())
            ->setIsCustomerNotified(
                $notifyCustomer
            )->setIsVisibleOnFront(
            $isVisibleOnFront
        )->setStatus(
            $status
        )->setComment($comment)->save();
        if ($notifyCustomer) {
            $this->commentSender->send(
                $parentOrder,
                $comment
            );
        }
        return $this;
    }

    /**
     * @param ParentOrder $parentOrder
     * @param $newStatus
     * @return $this|mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function changeStatus(ParentOrder $parentOrder, $newStatus)
    {
        /**
         *
         */
        $oldStatus = $parentOrder->getDetail()->getStatus();
        if ($oldStatus !== $newStatus && $parentOrder->getId()) {
            $state = $this->getStateFromStatus($newStatus);
            $parentOrder->getDetail()->setState($state)->setStatus($newStatus);
            $this->detailResource->save($parentOrder->getDetail());
        }
        return $this;
    }

    /**
     * @param string $status
     * @return string
     */
    private function getStateFromStatus(string $status)
    {
        $connection = $this->resourceConnection->getConnection();
        return (string) $connection->fetchOne(
            $connection->select()
                ->from(['sss' => 'sales_order_status_state'], [])
                ->where('status = ?', $status)
                ->limit(1)
                ->columns(['state'])
        );
    }
}
