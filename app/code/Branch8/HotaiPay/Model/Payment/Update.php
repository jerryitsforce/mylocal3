<?php
namespace Branch8\HotaiPay\Model\Payment;

use Branch8\GiftToFriend\Helper\Order as GiftOrderHelper;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Helper\Order\OrderManagement;
use Branch8\HotaiPay\Logger\Payment\Logger;
use Branch8\HotaiPoint\Helper\CacheLock as PointCacheLock;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Ecpay\General\Cron\OrderAutoProcedure;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;

class Update
{
    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    protected $checkoutSession;

    /** @var \Branch8\HotaiPay\Logger\Payment\Logger $logger */
    protected $logger;

    /** @var \Magento\Sales\Api\Data\OrderInterface $order */
    protected $order;

    /** @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder */
    protected $transactionBuilder;

    /** @var \Branch8\HotaiPay\Helper\Order\OrderManagement $orderManagement */
    protected $orderManagement;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /** @var \Magento\Sales\Model\OrderFactory $orderFactory */
    protected $orderFactory;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    /** @var \Ecpay\General\Cron\OrderAutoProcedure $orderAutoProcedure */
    protected $orderAutoProcedure;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var PointCacheLock */
    protected $pointCacheLock;

    protected $processId = "";

    /** @var \Branch8\GiftToFriend\Helper\Order $giftOrderHelper */
    protected $giftOrderHelper;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Session $checkoutSession,
        Logger $logger,
        OrderInterface $order,
        TransactionBuilder $transactionBuilder,
        OrderManagement $orderManagement,
        OrderRepositoryInterface $orderRepository,
        UpdateOrderStatus $updateOrderStatus,
        OrderFactory $orderFactory,
        HotaiPayLogHelper $hotaiPayLogHelper,
        OrderAutoProcedure $orderAutoProcedure,
        ManagerInterface $eventManager,
        PointCacheLock $pointCacheLock,
        GiftOrderHelper $giftOrderHelper
    ) {
        $this->checkoutSession    = $checkoutSession;
        $this->logger             = $logger;
        $this->order              = $order;
        $this->transactionBuilder = $transactionBuilder;
        $this->orderManagement    = $orderManagement;
        $this->orderRepository    = $orderRepository;
        $this->updateOrderStatus  = $updateOrderStatus;
        $this->orderFactory       = $orderFactory;
        $this->hotaiPayLogHelper  = $hotaiPayLogHelper;
        $this->orderAutoProcedure = $orderAutoProcedure;
        $this->eventManager       = $eventManager;
        $this->pointCacheLock     = $pointCacheLock;
        $this->giftOrderHelper    = $giftOrderHelper;

        $this->processId = getmypid();
    }

    /**
     * setStatusAfterCheckoutSuccess 付款成功後更改訂單狀態
     *
     * @param  int $parentOrder
     * @return void
     */
    public function setStatusAfterCheckoutSuccess(int $parentOrderId = null)
    {
        $this->updateStatus(
            State::STATE_PROCESSING,
            Status::STATUS_PROCESSING,
            $parentOrderId
        );
    }

    /**
     * setStatusBeforeCheckout 付款前更改訂單狀態
     *
     * @param  int $parentOrder
     * @return void
     */
    public function setStatusBeforeCheckout(int $parentOrderId = null)
    {
        $this->updateStatus(
            State::STATE_NEW,
            Status::STATUS_PENDING_PAYMENT,
            $parentOrderId
        );
    }

    /**
     * setFraud 變更詐欺訂單狀態
     *
     * @param  int $parentOrder
     * @return void
     */
    public function setFraud(int $parentOrderId = null)
    {
        $this->updateStatus(
            State::STATE_FRAUD,
            Status::STATUS_FRAUD,
            $parentOrderId
        );
    }

    /**
     * revertStatusDuringCheckout 回復訂單狀態
     *
     * @param  int $parentOrder
     * @return void
     */
    public function revertStatusDuringCheckout(int $parentOrderId = null)
    {
        $this->updateStatus(
            State::STATE_NEW,
            Status::STATUS_PENDING_PAYMENT,
            $parentOrderId
        );
    }

    /**
     * updateStatus
     *
     * @param  mixed $state
     * @param  mixed $status
     * @param  mixed $parentOrderId
     * @return void
     */
    public function updateStatus(string $state, string $status, int $parentOrderId = null)
    {
        $updateState  = $state;
        $updateStatus = $status;

        //If no parent order params then get parent order id from session
        if (! $parentOrderId) {
            $parentOrderId = $this->checkoutSession->getData('parentOrderId');
        }

        $parentOrder        = $this->orderManagement->getParentOrderByParentId($parentOrderId);
        $parentOrderDetail  = $parentOrder->getDetail();
        $parentOrderPayment = $this->orderManagement->getParentOrderPaymentByParentId($parentOrderId);

        //Update Parent Order Status
        if ($this->giftOrderHelper->isGiftOrder($parentOrderDetail)
            && $status == Status::STATUS_PROCESSING) {

            $updateStatus = Status::STATUS_GIFT_INFO_PENDING;

            // If gift info is confirmed
            if($this->giftOrderHelper->isGiftOrderConfirmed($parentOrderDetail)){
                $updateStatus = Status::STATUS_GIFT_INFO_COMPLETE;
            }
        }

        $parentOrderDetail
            ->setState($updateState)
            ->setStatus($updateStatus)
            ->save();

        //Update Child Order(s) Status
        $childOrderIds = explode(',', $parentOrder->getOrderIds());
        foreach ($childOrderIds as $childOrderId) {
            $isPaid = false;

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($childOrderId);

            //$this->orderFactory->create()->load($childOrderId);

            //票券不用強制更新
            if (! $order->getIsVirtual()) {
                $order->setIsForceUpdateStatus(true);
            }

            if ($status === Status::STATUS_PROCESSING) {
                if (! $this->pointCacheLock->checkIsPointCommitProcedureLockNow($order->getId())) {
                    $pointCacheLockMessage = "Point cache lock not found, add cache lock by current process, self process ID: {$this->processId}.";
                    $order->addStatusHistoryComment($pointCacheLockMessage)->save();

                    $this->pointCacheLock->pointCommitProcedureLock($order->getId(), $this->processId);
                } else {
                    $lockId                = $this->pointCacheLock->getPointCommitProcedureLockValue($order->getId());
                    $pointCacheLockMessage = "Found point cache lock, self process ID: {$this->processId}, locked process ID: {$lockId}.";
                    $order->addStatusHistoryComment($pointCacheLockMessage)->save();
                }

                $order->setIsPaid(1);
                $isPaid = true;
            }

            $order->setState($updateState)
                ->setStatus($updateStatus)
                ->addStatusHistoryComment(
                    __('Update Order Status to #%1.', $updateStatus));

            $this->orderRepository->save($order);

            /** get new status of order */
            // $order = $this->orderFactory->create()->load($childOrderId);
            foreach ($order->getAllVisibleItems() as $item) {
                $item->setFlowStatus($updateStatus);
                $item->save();

                $this->updateOrderStatus->addItemStatusRecord(
                    $order->getId(),
                    $item,
                    $updateStatus
                );
            }

            if ($status === Status::STATUS_PROCESSING) {
                $this->setAuthorizedOrder($order, $parentOrderPayment);
            }

            if ($isPaid) {
                if ($this->checkIfNeedPointCommitProcedure($order)) {
                    try {
                        $this->eventManager->dispatch(
                            "ecpay_invoice_issued_for_paid",
                            [
                                "orderId" => $childOrderId,
                            ]
                        );

                        if ($updateStatus == Status::STATUS_PROCESSING) {
                            $this->eventManager->dispatch(
                                "ecpay_inovice_ticket_item_arrived_check",
                                [
                                    "orderId" => $childOrderId,
                                ]
                            );
                        }
                    } catch (\Throwable $th) {
                        $this->hotaiPayLogHelper->writeLog(
                            [
                                "title"   => "Branch8\\HotaiPay\\Model\\Payment\\Update is_paid handle error.",
                                "message" => $th->getMessage(),
                                "trace"   => $th->getTraceAsString(),
                                "orderId" => $childOrderId,
                            ],
                            __CLASS__
                        );
                    }

                    // Let it realse by time
                    // $this->pointCacheLock->pointCommitProcedureUnlock($order->getId());
                }

                // $this->orderAutoProcedure->invoiceAutoProcess($childOrderId);
            }
        }
    }

    /**
     * setAuthorizedOrder 授權成功
     *
     * @param mixed $order
     * @param mixed $parentOrderPayment
     * @return void
     */
    private function setAuthorizedOrder($order, $parentOrderPayment)
    {
        $order->setTotalPaid($order->getGrandTotal());
        $order->setBaseTotalPaid($order->getGrandTotal());
        $order->setTotalDue(0);
        $order->save();

        $txn   = $parentOrderPayment->getData()['txn'];
        $txnId = '';
        if ($txn) {
            $txnId = substr($txn, -40); // TXN 後 40 碼
        }

        $this->createTransaction($order, ['TXN_SUBFIX' => $txnId]);
    }

    /**
     * setPaymentResult 設定付款結果
     *
     * @param  mixed $result
     * @return void
     */
    public function setPaymentResult($result)
    {
        $result = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : $result;

        $parentOrder = $this->orderManagement->getParentOrderPaymentByParentId($result['parentOrderId'] ?? $this->checkoutSession->getData('parentOrderId'));
        $parentOrder
            ->setGetPaymentResult($result)
            ->save();

        try {
            $this->hotaiPayLogHelper->writeLog('[setPaymentResult] Parent Order Id: ' . $this->checkoutSession->getData('parentOrderId'), __CLASS__);

            $this->hotaiPayLogHelper->writeLog($result, __CLASS__);

            $parentOrder   = $this->orderManagement->getParentOrderByParentId($this->checkoutSession->getData('parentOrderId'));
            $childOrderIds = explode(',', $parentOrder->getOrderIds());
            foreach ($childOrderIds as $childOrderId) {
                $order = $this->orderRepository->get($childOrderId);
                $order
                    ->addStatusHistoryComment(
                        __('Payment Response Result %1.', $result))
                    ->save();
            }
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }

    }

    /**
     * createTransaction
     *
     * @param  mixed $order
     * @param  mixed $paymentData
     * @return void
     */
    public function createTransaction($order = null, $paymentData = [])
    {
        try {
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['TXN_SUBFIX']);
            $payment->setTransactionId($paymentData['TXN_SUBFIX']);
            $payment->setAdditionalInformation(
                [Transaction::RAW_DETAILS => (array) $paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
            $message = __('The authorized amount is %1.', $formatedPrice);

            $trans       = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['TXN_SUBFIX'])
                ->setAdditionalInformation(
                    [Transaction::RAW_DETAILS => (array) $paymentData]
                )
                ->setFailSafe(true)
                ->build(TransactionInterface::TYPE_AUTH);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
            $transaction->save();
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }
    }

    /**
     * setPaymentFailedReason 設定付款失敗原因
     *
     * @param  mixed $result
     * @return void
     */
    public function setPaymentFailedReason($result)
    {

        $this->hotaiPayLogHelper->writeLog('--- Parent Order Id: ' . $this->checkoutSession->getData('parentOrderId') . '---', __CLASS__);

        $this->hotaiPayLogHelper->writeLog($result, __CLASS__);

        try {
            $parentOrder   = $this->orderManagement->getParentOrderByParentId($this->checkoutSession->getData('parentOrderId'));
            $childOrderIds = explode(',', $parentOrder->getOrderIds());
            foreach ($childOrderIds as $childOrderId) {
                $order = $this->orderRepository->get($childOrderId);
                $order
                    ->addStatusHistoryComment(
                        __('Failed Payment Reason %1.', $result))
                    ->save();
            }
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }

    }

    /**
     * getPaymentMac 取得 Mac 值
     *
     * @param  mixed $result
     * @return null | string
     */
    public function getPaymentMac($parentOrderId = null)
    {
        try {
            $parentOrder = $this->orderManagement->getParentOrderPaymentByParentId($parentOrderId ?? $this->checkoutSession->getData('parentOrderId'));
            return $parentOrder->getMac();
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }

    }
    protected function checkIfNeedPointCommitProcedure($order): bool
    {
        $cond1 = $this->pointCacheLock->checkIsPointCommitProcedureLockNow($order->getId());

        $lockId = $this->pointCacheLock->getPointCommitProcedureLockValue($order->getId());
        $cond2  = $lockId == $this->processId;

        if ($cond1 && $cond2) {
            $pointCacheLockMessage = "Pass checkIfNeedPointCommitProcedure check, self process ID: {$this->processId}, locked process ID: {$lockId}.";
            $order->addStatusHistoryComment($pointCacheLockMessage)->save();
        }

        return $cond1 && $cond2;
    }

    /**
     * checkSubOrderIsPaidOrNot
     *
     * @param  mixed $result
     * @return bool
     */
    public function checkSubOrderIsPaid($result)
    {
        $result = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : $result;

        try {
            $parentOrder       = $this->orderManagement->getParentOrderByParentId($this->checkoutSession->getData('parentOrderId'));
            $parentOrderDetail = $parentOrder->getDetail();
            $parentOrderStatus = $parentOrderDetail->getStatus();

            /** 已付款 */
            if (! in_array($parentOrderStatus, [Status::STATUS_PENDING_PAYMENT, Status::STATUS_PENDING])) {
                $this->hotaiPayLogHelper->writeLog('ParentOrderId: ' . $this->checkoutSession->getData('parentOrderId') . ' Not in pending_payment or pending status.', __CLASS__);

                return true;
            }

            $childOrderIds = explode(',', $parentOrder->getOrderIds());
            foreach ($childOrderIds as $childOrderId) {
                $order = $this->orderRepository->get($childOrderId);
                if ($order->getData('is_paid')) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }

        return false;
    }
}
