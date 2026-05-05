<?php

namespace Branch8\Refund\Helper;

use Branch8\CTBC\Helper\Response\CurrentState;
use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Model\OrderManagement;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Refund\Model\SalesRefundFactory;
use Exception;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Coordinates CTBC refund API calls, schedule list, and order comments for refund flow.
 */
class RefundOperation
{
    public const REFUND_STATUS_COMPLETE_CODE = 'closed_refund_success';
    public const REFUND_STATE_COMPLETE_CODE = 'closed';
    public const TRANSACTION_SUCCESS_CODE = '00';
    public const REFUND_SUCCESS = 'success';
    public const REFUND_FAIL = 'fail';
    public const REFUND_ERROR_MSG = 'Cannot Refund This Order.';
    /** @var array<int, string> */
    public const REFUND_METHOD = ['hotaipay']; /** TODO: NEED to Remove in order to apply to ALL payment Method */
    public const TYPE_SCHEDULE = 'schedule';
    public const TYPE_EXECUTE = 'execute';

    /**
     * Configurable log class key (must match Branch8\Refund\Model\Config\Source\LogFileOption values).
     */
    private const LOG_CLASS_KEY = 'RefundOperation';

    /** @var Api */
    protected $refundApi;

    /** @var OrderInterface */
    protected $orderRepository;

    /** @var mixed */
    protected $order;

    /** @var bool */
    protected $isChildOrder;

    /** @var int */
    protected $orderId;

    /** @var int */
    protected $amount;

    /** @var ParentOrder */
    protected $parentOrder;

    /** @var OrderManagement */
    protected $ctbcOrdermanagement;

    /** @var SalesRefundFactory */
    protected $salesRefundFactory;

    /** @var DateTime */
    private $date;

    /** @var int */
    private $type = \Branch8\Refund\Model\SalesRefund::TYPE_REFUND;

    /** @var mixed */
    private $functionType;

    /** @var mixed */
    protected $point;

    /** @var mixed */
    public $currentStateLabel;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param DateTime $date Magento date helper
     * @param Api $refundApi CTBC refund API client
     * @param OrderInterface $orderRepository Order repository for loads and comments
     * @param ParentOrder $parentOrder Parent marketplace order resource
     * @param OrderManagement $ctbcOrdermanagement CTBC order helpers
     * @param SalesRefundFactory $salesRefundFactory Refund entity factory
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        DateTime $date,
        Api $refundApi,
        OrderInterface $orderRepository,
        ParentOrder $parentOrder,
        OrderManagement $ctbcOrdermanagement,
        SalesRefundFactory $salesRefundFactory,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->refundApi = $refundApi;
        $this->orderRepository = $orderRepository;
        $this->parentOrder = $parentOrder;
        $this->ctbcOrdermanagement = $ctbcOrdermanagement;
        $this->salesRefundFactory = $salesRefundFactory;
        $this->date = $date;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Set refund record type (e.g. refund vs cancel).
     *
     * @param int $type SalesRefund type constant
     * @return void
     */
    public function setRefundType($type)
    {
        $this->type = $type;
    }

    /**
     * Add refund request to schedule list and persist SalesRefund row.
     *
     * @param int $orderId Child or parent order id
     * @param int $amount Refund amount
     * @param int $point Refund points
     * @param bool $isChildOrder Whether orderId refers to a child order
     * @return \Branch8\Refund\Model\SalesRefund
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setToScheduleList($orderId, $amount = 0, $point = 0, $isChildOrder = true)
    {
        $this->orderId = $orderId;
        $this->isChildOrder = $isChildOrder;
        $this->amount = $amount;
        $this->point = $point;
        $this->functionType = self::TYPE_SCHEDULE;

        $parentOrder = $this->ctbcOrdermanagement->getParentOrder($this->isChildOrder, $this->orderId);

        try {
            $this->refundApi->setOrderInfo($parentOrder);
            $refundRecord = $this->iniRefundRecord(
                $parentOrder,
                $this->ctbcOrdermanagement->getOrderPurchAmt($parentOrder)
            );

            $comment = __("Set Refunding Schedule Amount $%1", $this->amount);
            $this->updateOrderComment($parentOrder, $comment);
        } catch (\Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'setToScheduleList');

            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage()),
                $e
            );
        }

        return $refundRecord;
    }

    /**
     * Load CTBC inquiry and validate order can be canceled/refunded.
     *
     * @param \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail $parentOrder Parent order model
     * @return array<string, mixed> Inquiry payload
     * @throws \Exception When CTBC says order cannot be canceled/refunded
     */
    public function getInqueryResult($parentOrder)
    {
        $inquiryResult = $this->ctbcOrdermanagement->inquiryOrderStatus($parentOrder);

        if (!$this->canCancelOrRefund($inquiryResult)) {
            throw new \Exception(__('This Order Cannot be Canceled/Refunded.'));
        }

        return $inquiryResult;
    }

    /**
     * Execute refund transaction against CTBC for the given order.
     *
     * @param int $orderId Order id
     * @param int $amount Refund amount (0 = full per rules)
     * @param bool $isChildOrder Whether orderId is a child order
     * @return string|null success|fail|null
     */
    public function execute($orderId, $amount = 0, $isChildOrder = true)
    {
        $this->orderId = $orderId;
        $this->isChildOrder = $isChildOrder;
        $this->amount = $amount;
        $this->functionType = self::TYPE_EXECUTE;

        $parentOrder = $this->ctbcOrdermanagement->getParentOrder($this->isChildOrder, $this->orderId);

        $this->refundApi->setOrderInfo($parentOrder);

        try {
            $inquiryResult = $this->getInqueryResult($parentOrder);

            $result = $this->callRefundApi($inquiryResult);

            if (!$this->isSuccess($result)) {
                throw new \Exception(_('Refund Transaction is Not Successful.'));
            }

            $this->refundLogger->log(
                self::LOG_CLASS_KEY,
                '[PaymentReversal] success orderId=' . $this->orderId
                . ' ErrCode=' . ($result['ErrCode'] ?? '')
            );

            $inquiryResult = $this->ctbcOrdermanagement->inquiryOrderStatus($parentOrder);
            $currentStateLabel = __(CurrentState::LABEL[$inquiryResult['CurrentState']]) ?? '';
            $this->setCurrentStateLabel($currentStateLabel);

            if ($this->isChildOrder) {
                $comment = __("Refund Status From CTBC changes to $currentStateLabel");
                $this->updateOrderComment($parentOrder, $comment);
            }

            return self::REFUND_SUCCESS;
        } catch (\Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'execute');

            return self::REFUND_FAIL;
        }
    }

    /**
     * Append comment on child or all child orders under parent.
     *
     * @param mixed $parentOrder Parent order entity
     * @param string $comment Status history text
     * @return void
     */
    public function updateOrderComment($parentOrder, $comment)
    {
        if ($this->isChildOrder) {
            $this->updateChildOrderStatus($this->orderId, $comment);

            return;
        }

        $childOrderIds = $this->parentOrder->getSubOrders((int) $parentOrder->getParentId());
        foreach ($childOrderIds as $childOrderId) {
            $this->updateChildOrderStatus($childOrderId, $comment);
        }
    }

    /**
     * Add status history comment on a single order.
     *
     * @param int|string $orderId Order entity id
     * @param string $comment Comment text
     * @return void
     */
    public function updateChildOrderStatus($orderId, $comment)
    {
        $order = $this->orderRepository->load($orderId);
        $order->addStatusHistoryComment(__($comment))->save();
    }

    /**
     * Whether refund/cancel is allowed from inquiry CurrentState (execute mode logs inquiry payload).
     *
     * @param array<string, mixed> $statusArray CTBC inquiry response
     * @return bool
     */
    private function canCancelOrRefund($statusArray): bool
    {
        if ($this->functionType == self::TYPE_SCHEDULE) {
            return true;
        }

        $this->refundLogger->log(
            self::LOG_CLASS_KEY,
            "[Inquiry Check Result] " . json_encode($statusArray, JSON_UNESCAPED_UNICODE)
        );

        $validState = [
            CurrentState::AUTHORIZE_SUCCESS,
            CurrentState::PAYMENT_REQUEST,
            CurrentState::PAYMENT_REQUEST_PROCESSING,
            CurrentState::PAYMENT_REQUEST_SUCCESS,
            CurrentState::REFUND_SUCCESS,
            CurrentState::REFUND_REQUEST,
        ];

        $stateCheck = isset($statusArray['CurrentState']) && in_array($statusArray['CurrentState'], $validState);

        return $stateCheck;
    }

    /**
     * Dispatch CTBC refund/cancel API by CurrentState.
     *
     * @param array<string, mixed> $inqueryArray Inquiry row including CurrentState, amount, XID
     * @return array<string, mixed>
     */
    private function callRefundApi($inqueryArray)
    {
        $inqueryArray['orgAmt'] = $inqueryArray['amount'];
        $inqueryArray['AuthRRPID'] = trim($inqueryArray['XID']);
        $state = (int) ($inqueryArray['CurrentState'] ?? 0);

        switch ($inqueryArray['CurrentState']) {
            case CurrentState::AUTHORIZE_SUCCESS:
                $this->refundLogger->log(
                    self::LOG_CLASS_KEY,
                    '[PaymentReversal] orderId=' . $this->orderId . ' action=authRevTransacOrder state=' . $state
                );
                $inqueryArray['authnewAmt'] = $this->setAuthnewAmt($inqueryArray);

                return $this->refundApi->authRevTransacOrder($inqueryArray);
            case CurrentState::PAYMENT_REQUEST:
            case CurrentState::PAYMENT_REQUEST_PROCESSING:
                $this->refundLogger->log(
                    self::LOG_CLASS_KEY,
                    '[PaymentReversal] orderId=' . $this->orderId . ' action=capRevProcessor state=' . $state
                );

                return $this->capRevProcessor($inqueryArray);
            case CurrentState::PAYMENT_REQUEST_SUCCESS:
            case CurrentState::REFUND_SUCCESS:
                $this->refundLogger->log(
                    self::LOG_CLASS_KEY,
                    '[PaymentReversal] orderId=' . $this->orderId . ' action=credTransacOrder state=' . $state
                );
                $inqueryArray['credAmt'] = $this->setCredAmt($inqueryArray);

                return $this->refundApi->credTransacOrder($inqueryArray);
            case CurrentState::REFUND_REQUEST:
                $this->refundLogger->log(
                    self::LOG_CLASS_KEY,
                    '[PaymentReversal] orderId=' . $this->orderId . ' action=credRevTransacOrder state=' . $state
                );
                $inqueryArray['orgAmt'] = $this->amount;

                return $this->refundApi->credRevTransacOrder($inqueryArray);
            default:
                return [];
        }
    }

    /**
     * Reverse capture then authorization when in payment-request states.
     *
     * @param array<string, mixed> $inqueryArray Inquiry payload
     * @return array<string, mixed>|null
     */
    public function capRevProcessor($inqueryArray)
    {
        try {
            if ($inqueryArray['orgAmt'] != $this->amount) {
                throw new \Exception(__('capRevProcessor stopped. Cannot request partial refund order.'));
            }

            $result = $this->refundApi->capRevTransacOrder($inqueryArray);
            if ($this->isSuccess($result)) {
                $inqueryArray['CurrentState'] = CurrentState::AUTHORIZE_SUCCESS;

                return $this->callRefundApi($inqueryArray);
            }

            return $result;
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'capRevProcessor');
        }

        return [];
    }

    /**
     * Resolve credit/refund amount for credTransacOrder.
     *
     * @param array<string, mixed> $inqueryArray Inquiry payload
     * @return float|int|string
     */
    private function setCredAmt($inqueryArray)
    {
        if ($this->amount != 0) {
            return $this->amount;
        }

        $credAmt = $inqueryArray['amount'];

        if ($this->isChildOrder) {
            $order = $this->orderRepository->load($this->orderId);
            $credAmt = $order->getGrandTotal();
        }

        return $credAmt;
    }

    /**
     * @param array<string, mixed> $result CTBC response
     * @return bool
     */
    private function isSuccess($result): bool
    {
        return !empty($result) && isset($result['ErrCode']) && $result['ErrCode'] == self::TRANSACTION_SUCCESS_CODE;
    }

    /**
     * Validate partial refund rules for authorize reversal.
     *
     * @param array<string, mixed> $inqueryArray Inquiry payload
     * @return int Always 0 when valid
     * @throws \Exception When partial refund is not allowed
     */
    private function setAuthnewAmt(array $inqueryArray)
    {
        $newAuthAmt = 0;

        if ($inqueryArray['orgAmt'] != $this->amount) {
            $this->refundLogger->log(
                self::LOG_CLASS_KEY,
                json_encode([
                    $this->orderId => [
                        'amount' => $this->amount,
                        'inqueryArray' => $inqueryArray,
                    ],
                ])
            );

            throw new \Exception(_('This Order Cannot Be Partial Refund.'));
        }

        return $newAuthAmt;
    }

    /**
     * Create SalesRefund row and mark orders as having refund in progress.
     *
     * @param mixed $parentOrder Parent order
     * @param float|int|string $orderAmount Parent purchase amount
     * @return \Branch8\Refund\Model\SalesRefund
     */
    private function iniRefundRecord($parentOrder, $orderAmount)
    {
        $requestPayload = [
            'orderId' => $this->orderId,
            'amount' => $this->amount,
            'isChildOrder' => $this->isChildOrder,
        ];

        $this->updateOrderHasRefund($parentOrder);

        $salesRefundRecord = $this->salesRefundFactory->create();
        $salesRefundRecord->setIncrementId($parentOrder->getIncrementId());
        $salesRefundRecord->setParentOrderId($parentOrder->getParentId());
        $salesRefundRecord->setParentOrderAmount($orderAmount);
        $salesRefundRecord->setRefundAmount($this->amount);
        $salesRefundRecord->setRefundPoint($this->point);
        $salesRefundRecord->setType($this->type);
        $salesRefundRecord->setIsRefunded(false);
        $salesRefundRecord->setIsTriggered(false);
        $salesRefundRecord->setIsNotifiedAdmin(false);
        $salesRefundRecord->setRequestPayload(json_encode($requestPayload, JSON_UNESCAPED_UNICODE));
        $salesRefundRecord->setCreatedAt($this->date->date());
        $salesRefundRecord->save();

        return $salesRefundRecord;
    }

    /**
     * @param bool $isChildOrder Whether subsequent operations use a single child order id
     * @return void
     */
    public function setIsChildOrder(bool $isChildOrder)
    {
        $this->isChildOrder = $isChildOrder;
    }

    /**
     * @param int $orderId Active order id for this operation
     * @return void
     */
    public function setOrderId(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Set has_refund flag on child or all children.
     *
     * @param mixed $parentOrder Parent order
     * @return void
     */
    public function updateOrderHasRefund($parentOrder)
    {
        if ($this->isChildOrder) {
            $this->updateChildOrderHasRefund($this->orderId);

            return;
        }

        $childOrderIds = $this->parentOrder->getSubOrders((int) $parentOrder->getParentId());
        foreach ($childOrderIds as $childOrderId) {
            $this->updateChildOrderHasRefund($childOrderId);
        }
    }

    /**
     * @param int $orderId Order id
     * @return void
     */
    public function updateChildOrderHasRefund($orderId)
    {
        $order = $this->orderRepository->load($orderId);
        $order->setHasRefund(true)->save();
    }

    /**
     * @param string|\Magento\Framework\Phrase $currentStateLabel Human-readable CTBC state
     * @return void
     */
    public function setCurrentStateLabel($currentStateLabel)
    {
        $this->currentStateLabel = $currentStateLabel;
    }

    /**
     * @return mixed
     */
    public function getCurrentStateLabel()
    {
        return $this->currentStateLabel;
    }
}
