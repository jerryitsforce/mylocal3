<?php
namespace Branch8\Refund\Cron;

use Branch8\CTBC\Helper\Response\CurrentState;
use Branch8\CTBC\Helper\Response\QueryCode;
use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Model\OrderManagement;
use Branch8\Customer\Model\GetCustomerNickname;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\HotaiPay\Helper\Order\OrderManagement as HotaiPayOrderManagement;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;
use Branch8\Refund\Helper\CreditmemoRefundData;
use Branch8\Refund\Helper\Email;
use Branch8\Refund\Helper\RefundOperation;
use Branch8\Refund\Helper\RmaRefundData;
use Branch8\Refund\Model\ResourceModel\SalesRefund\CollectionFactory;
use Branch8\Refund\Model\SalesRefund\Status;
use Branch8\CTBC\Helper\OrderStatus;
use Exception;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\State;

class RefundStatusCheckInquiry
{
    /**
     * Admin multiselect value for this cron class (see LogFileOption).
     */
    private const LOG_CLASS_KEY = 'RefundStatusCheckInquiry';

    /** @var CollectionFactory */
    protected $refundCollection;

    /** @var Api */
    protected $api;

    /** @var OrderManagement */
    protected $orderManagement;

    /** @var RefundOperation */
    protected $refundOperation;

    /** @var Email */
    protected $email;

    /** @var CreditmemoRefundData */
    protected $creditmemoRefundData;

    /** @var RmaRefundData */
    protected $rmaRefundData;

    /** @var GetCustomerNickname */
    private $customerNickname;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var HotaiPayOrderManagement */
    private $hotaiPayOrderManagement;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /** @var TimezoneInterface */
    protected $localeDate;

    /** @var OrderStatus */
    protected $orderStatus;

    /** @var array */
    protected $abnormalOrderList = [];

    /** @var array */
    public $failedRefundedCollection = [];

    /** @var array */
    public $successfulRefundedCollection = [];

    /** @var mixed */
    protected $refundResult;

    /** @var mixed */
    private $salesRefund = null;

    /**
     * @param Api $api
     * @param State $state
     * @param CollectionFactory $refundCollection
     * @param OrderManagement $orderManagement
     * @param RefundOperation $refundOperation
     * @param Email $email
     * @param CreditmemoRefundData $creditmemoRefundData
     * @param RmaRefundData $rmaRefundData
     * @param GetCustomerNickname $customerNickname
     * @param UrlInterface $urlBuilder
     * @param HotaiPayOrderManagement $hotaiPayOrderManagement
     * @param ConfigurableRefundLogger $refundLogger Gated refund file logger
     * @param TimezoneInterface $localeDate Store timezone helper
     * @param OrderStatus $orderStatus CTBC order status helper
     */
    public function __construct(
        Api $api,
        State $state,
        CollectionFactory $refundCollection,
        OrderManagement $orderManagement,
        RefundOperation $refundOperation,
        Email $email,
        CreditmemoRefundData $creditmemoRefundData,
        RmaRefundData $rmaRefundData,
        GetCustomerNickname $customerNickname,
        UrlInterface $urlBuilder,
        HotaiPayOrderManagement $hotaiPayOrderManagement,
        ConfigurableRefundLogger $refundLogger,
        TimezoneInterface $localeDate,
        OrderStatus $orderStatus
    ) {
        $this->api = $api;
        $this->refundCollection = $refundCollection;
        $this->orderManagement = $orderManagement;
        $this->refundOperation = $refundOperation;
        $this->email = $email;
        $this->creditmemoRefundData = $creditmemoRefundData;
        $this->rmaRefundData = $rmaRefundData;
        $this->customerNickname = $customerNickname;
        $this->urlBuilder = $urlBuilder;
        $this->hotaiPayOrderManagement = $hotaiPayOrderManagement;
        $this->refundLogger = $refundLogger;
        $this->localeDate = $localeDate;
        $this->orderStatus = $orderStatus;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        $this->logMessage("------Start Of Cron Refund Status Check Inquiry-----");

        try {
            $refundCollection = $this->getRefundCollectionList();
            $this->processRefundCollection($refundCollection);
            $this->updateCreditMemoAndRmaStatus();
            $this->notifyAdminIfAbnormalOrdersExist();
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'execute');
        }

        $this->logMessage("------End Of Cron Refund Status Check Inquiry-----");
    }

    /**
     * Process refund collection
     *
     * @param \Branch8\Refund\Model\ResourceModel\SalesRefund\Collection $collection
     * @return void
     */
    protected function processRefundCollection($collection)
    {
        foreach ($collection as $order) {
            $this->salesRefund = null;

            try {
                $this->processRefundOrder($order);
            } catch (Exception $e) {
                $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'processRefundCollection');
                continue;
            }
        }
    }

    /**
     * Process a single refund order
     *
     * @param mixed $order
     * @return void
     */
    protected function processRefundOrder($order)
    {
        $this->logMessage("Start Checking Order Id: " . $order->getIncrementId());
        $this->initOrder($order);

        // Check if order cannot be refunded
        if ($this->handleCannotRefundOrder($order)) {
            return;
        }

        // Handle zero amount refund
        if ($this->handleZeroAmountRefund($order)) {
            return;
        }

        // Check refund status - get orderId from request_payload
        $requestPayload = json_decode($order->getRequestPayload(), true);
        $this->refundResult = $this->orderStatus->getRefundDataByOrderId((int) $requestPayload['orderId']);        
        
        $this->logInquiryResult();
        $order->setResponseInformation(json_encode($this->refundResult, JSON_UNESCAPED_UNICODE));

        // Check for abnormal result
        if ($this->handleAbnormalResult($order)) {
            return;
        }

        // Check if final status reached
        if (!$this->isGetRefundFinalStatus()) {
            $order->save();
            $this->logMessage("Not in the last State");
            return;
        }

        // Process final refund status
        $this->processFinalRefundStatus($order);
    }

    /**
     * Handle order that cannot be refunded
     *
     * @param mixed $order
     * @return bool True if handled, false otherwise
     */
    protected function handleCannotRefundOrder($order)
    {
        if ($order->getStatus() != Status::CANNOT_REFUND) {
            return false;
        }

        $this->failedRefundedCollection[] = $order;
        $order->setIsNotifiedAdmin(true);
        $order->setStatus(Status::REFUND_FAILED);
        $order->save();

        $this->logMessage("This order cannot be refunded.");
        return true;
    }

    /**
     * Handle zero amount refund
     *
     * @param mixed $order
     * @return bool True if handled, false otherwise
     */
    protected function handleZeroAmountRefund($order)
    {
        if ($order->getRefundAmount() === null || (int) $order->getRefundAmount() >= 1) {
            return false;
        }

        $order->setStatus(Status::REFUND_COMPLETE);
        $order->setIsRefunded(true);
        $order->setRefundedAt(new \DateTime());
        $order->save();

        $comment = __("Cron Inquiry Check: There is no need to request CTBC refund since refund amount equal or less than 0.");
        $this->updateOrderComment($order, $comment);
        $this->successfulRefundedCollection[] = $order;

        $this->logMessage($comment);
        return true;
    }

    /**
     * Handle abnormal refund result
     *
     * @param mixed $order
     * @return bool True if abnormal, false otherwise
     */
    protected function handleAbnormalResult($order)
    {
        if (!$this->isAbnomalResult()) {
            return false;
        }

        $this->abnormalOrderList[] = $order->getIncrementId();
        $this->failedRefundedCollection[] = $order;
        $order->setIsNotifiedAdmin(true);
        $order->setStatus(Status::REFUND_FAILED);
        $order->save();

        $this->logMessage("Order Failed Refund.");
        return true;
    }

    /**
     * Process final refund status
     *
     * @param mixed $order
     * @return void
     */
    protected function processFinalRefundStatus($order)
    {
        $currentState = $this->refundResult['CurrentState'] ?? '';
        $currentStateLabel = CurrentState::LABEL[$currentState] ?? '';
        $comment = __("Cron Inquiry Check: Refund CurrentState Status From CTBC changes to %1", $currentState);
        $this->updateOrderComment($order, $comment);

        // Collect refund results
        if ($currentState == CurrentState::REFUND_FAIL) {
            $this->failedRefundedCollection[] = $order;
        } else {
            $this->successfulRefundedCollection[] = $order;
        }

        $order->setStatus(Status::REFUND_COMPLETE);
        $order->setRefundedAmount($order->getRefundAmount());
        $order->setIsRefunded(true);
        $order->setRefundedAt(new \DateTime());
        $order->save();

        $this->salesRefund = $order;
        $this->notifyCustomerRefundResult();
    }

    /**
     * Log inquiry result
     *
     * @return void
     */
    protected function logInquiryResult()
    {
        try {
            $this->logMessage(
                "[Inquiry Check Result] " . json_encode($this->refundResult, JSON_UNESCAPED_UNICODE)
            );
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'logInquiryResult');
        }
    }

    /**
     * Notify admin if abnormal orders exist
     *
     * @return void
     */
    protected function notifyAdminIfAbnormalOrdersExist()
    {
        if (!empty($this->abnormalOrderList)) {
            $this->notifyAdminAbnormalOrderList();
        }
    }

    /**
     * Notify customer about refund result
     *
     * @return void
     */
    protected function notifyCustomerRefundResult()
    {
        try {
            /** @var ParentOrderDetail $parentOrderDetail */
            $parentOrderDetail = $this->api->getOrderInfo();
            $emailTemplateId = $this->getRefundEmailTemplate($this->refundResult['CurrentState'] ?? '');

            $this->email->setEmailTemplateId($emailTemplateId);
            $this->email->setEmailReceivers($parentOrderDetail->getCustomerEmail());

            $emailVars = $this->buildEmailVars($parentOrderDetail);
            $this->email->setEmailVars($emailVars);
            $this->email->send();

            $this->logMessage(
                "[RefundStatusCheckInquiry][NotifyCustomerRefundResult] Send Mail To Customer, Email: " .
                $parentOrderDetail->getCustomerEmail()
            );
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'notifyCustomerRefundResult');
        }
    }

    /**
     * Build email variables
     *
     * @param ParentOrderDetail $parentOrderDetail
     * @return array
     */
    protected function buildEmailVars($parentOrderDetail)
    {
        $emailVars = [
            'increment_id' => $parentOrderDetail->getIncrementId(),
            'customer_name' => $this->customerNickname->getCustomerNicknameByCustomerId(
                $parentOrderDetail->getCustomerId()
            ),
            'order_link' => $this->getOrderUrlDetail($parentOrderDetail->getIncrementId()),
            'order_date' => $this->getOrderDate($parentOrderDetail->getCreatedAt()),
        ];

        if ($this->salesRefund && $this->salesRefund->getStatus() == Status::REFUND_COMPLETE) {
            $emailVars['refunded_amount'] = $this->salesRefund->getRefundedAmount();
            $emailVars['refunded_point'] = $this->rmaRefundData->getRefundedPointByMemoId(
                $this->salesRefund->getMemoId()
            );
            $emailVars['refunded_at'] = $this->salesRefund->getRefundedAt();
            $emailVars['shipping_description'] = $parentOrderDetail->getShippingDescription();
            $emailVars['memo_items'] = $this->getMemoItemsHtml(
                $this->creditmemoRefundData->getCreditMemoItems($this->salesRefund->getMemoId())
            );
            $emailVars['payment_info'] = $this->getPaymentInfo($parentOrderDetail);
        }

        return $emailVars;
    }

    /**
     * Get order date formatted
     *
     * @param string $createdAt
     * @return string
     */
    public function getOrderDate($createdAt)
    {
        try {
            return $this->localeDate->date($createdAt)->format('Y/m/d H:i');
        } catch (Exception $exception) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $exception, 'getOrderDate');

            return $createdAt;
        }
    }

    /**
     * Get payment info
     *
     * @param ParentOrderDetail $parentOrderDetail
     * @return string
     */
    public function getPaymentInfo($parentOrderDetail)
    {
        $payment = $this->hotaiPayOrderManagement->getParentOrderPaymentByParentId(
            $parentOrderDetail->getParentId()
        );

        if ($payment && $payment->getCcLast4()) {
            return strtoupper($payment->getCcType()) . ' **** **** **** ' . $payment->getCcLast4();
        }

        return '';
    }

    /**
     * Get RMA details
     *
     * @param int $memoId
     * @return mixed
     */
    public function getRmaDetails($memoId)
    {
        return $this->rmaRefundData->findRmaRecordByMemoId($memoId);
    }

    /**
     * Get memo items HTML
     *
     * @param \Magento\Sales\Model\Order\Creditmemo\Item[] $items
     * @return string
     */
    public function getMemoItemsHtml($items)
    {
        $itemsHtml = '<ul>';

        foreach ($items as $item) {
            $itemsHtml .= '<li>' . $item->getName() . ' x ' . (int) $item->getQty() . '</li>';
        }

        $itemsHtml .= '</ul>';

        return $itemsHtml;
    }

    /**
     * Get order URL detail
     *
     * @param string $parentOrderNumber
     * @return string
     */
    public function getOrderUrlDetail($parentOrderNumber)
    {
        if ($parentOrderNumber) {
            return $this->urlBuilder->getUrl(
                'sales/parentOrder/history',
                ['_query' => ['search' => $parentOrderNumber]]
            );
        }

        return $this->urlBuilder->getUrl('sales/parentOrder/history');
    }

    /**
     * Notify admin about abnormal order list
     *
     * @return void
     */
    protected function notifyAdminAbnormalOrderList()
    {
        try {
            $this->email->setEmailTemplateId(Email::REFUND_ABNORMAL_EMAIL_TEMPLATE);
            $this->email->setEmailReceivers($this->email->getAbnormalAdminReceviers());
            $this->email->setEmailVars([
                'incrementIds' => implode(",", $this->abnormalOrderList),
            ]);
            $this->email->send();
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'notifyAdminAbnormalOrderList');
        }
    }

    /**
     * Check if result is abnormal
     *
     * @return bool
     */
    public function isAbnomalResult()
    {
        try {
            if (!$this->refundResult) {
                return false;
            }

            $queryCodeCheck = in_array(
                $this->refundResult['QueryCode'] ?? null,
                [QueryCode::ERR, QueryCode::NONE]
            );
            $errStatusCheck = ($this->refundResult['ErrCode'] ?? '') !== '00' ||
                !empty($this->refundResult['ERRDESC'] ?? '');

            return $queryCodeCheck || $errStatusCheck;
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'isAbnomalResult');

            return true;
        }
    }

    /**
     * Get refund email template
     *
     * @param string $template
     * @return string
     */
    private function getRefundEmailTemplate(string $template = '')
    {
        switch ($template) {
            case CurrentState::REFUND_SUCCESS:
            case CurrentState::CANCEL_ORDER:
                return Email::REFUND_SUCCESS_EMAIL_TEMPLATE;
            case CurrentState::REFUND_FAIL:
                return Email::REFUND_DECLINE_EMAIL_TEMPLATE;
            default:
                return Email::REFUND_ABNORMAL_EMAIL_TEMPLATE;
        }
    }

    /**
     * Update order comment
     *
     * @param mixed $refundOrder
     * @param string $comment
     * @return void
     */
    protected function updateOrderComment($refundOrder, $comment)
    {
        $order = $this->api->getOrderInfo();
        $requestPayload = json_decode($refundOrder->getRequestPayload(), true);

        $this->refundOperation->setOrderId((int) $requestPayload['orderId']);
        $this->refundOperation->setIsChildOrder((bool) $requestPayload['isChildOrder']);
        $this->refundOperation->updateOrderComment($order, $comment);
    }

    /**
     * Check if refund has reached final status
     *
     * @return bool
     */
    protected function isGetRefundFinalStatus()
    {
        $finalStatus = [
            CurrentState::REFUND_SUCCESS,
            CurrentState::REFUND_FAIL,
            CurrentState::CANCEL_ORDER,
        ];

        return isset($this->refundResult['CurrentState']) &&
            in_array($this->refundResult['CurrentState'], $finalStatus);
    }

    /**
     * Initialize order
     *
     * @param mixed $order
     * @return void
     */
    protected function initOrder($order)
    {
        $requestPayload = json_decode($order->getRequestPayload(), true);
        $parentOrder = $this->orderManagement->getParentOrder(
            $requestPayload['isChildOrder'],
            $requestPayload['orderId']
        );

        $this->api->setOrderInfo($parentOrder);
    }

    /**
     * Get refund collection list
     *
     * @return \Branch8\Refund\Model\ResourceModel\SalesRefund\Collection
     */
    protected function getRefundCollectionList()
    {
        return $this->refundCollection->create()
            ->addFieldToFilter('is_refunded', false)
            ->addFieldToFilter('is_notified_admin', false)
            ->addFieldToFilter('status', [
                'in' => [
                    Status::TRIGGERED,
                    Status::CANNOT_REFUND,
                    Status::CANNOT_TRIGGERED,
                ],
            ])
            ->addFieldToFilter('memo_id', ['neq' => 'NULL']);
    }

    /**
     * Update credit memo and RMA status
     *
     * @return void
     */
    protected function updateCreditMemoAndRmaStatus()
    {
        $this->updateCreditMemoDataAndRma(
            $this->failedRefundedCollection,
            \Branch8\Sales\Model\CreditMemo\CreditMemoStatus::REFUND_FAIL
        );

        $this->updateCreditMemoDataAndRma(
            $this->successfulRefundedCollection,
            \Branch8\Sales\Model\CreditMemo\CreditMemoStatus::REFUND_SUCCESS
        );
    }

    /**
     * Update credit memo data and RMA
     *
     * @param array $collection
     * @param string $memoStatus
     * @return void
     */
    protected function updateCreditMemoDataAndRma($collection, $memoStatus)
    {
        foreach ($collection as $record) {
            if ($record->getMemoId() === null) {
                $this->logMessage("There is no memo id");
                continue;
            }

            $data = [
                'creditmemo_status' => $memoStatus,
                'refunded_amount' => $record->getRefundedAmount(),
            ];

            $this->creditmemoRefundData->setRefundedData($record->getMemoId(), $data);
            $this->rmaRefundData->setRmaRefund($record->getMemoId(), $data);
        }
    }

    /**
     * Log message
     *
     * @param string $message
     * @return void
     */
    protected function logMessage($message)
    {
        $this->refundLogger->log(self::LOG_CLASS_KEY, (string) $message);
    }

    /**
     * Log error line (same gating as logMessage).
     *
     * @param string $message Error text
     * @return void
     */
    protected function logError($message)
    {
        $this->refundLogger->log(self::LOG_CLASS_KEY, (string) $message);
    }
}