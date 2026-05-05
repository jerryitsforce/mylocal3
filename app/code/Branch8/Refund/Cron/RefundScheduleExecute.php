<?php

namespace Branch8\Refund\Cron;

use Branch8\CTBC\Model\Api;
use Branch8\HotaiPay\Helper\Order\OrderManagement;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\CreditmemoRefundData;
use Branch8\Refund\Helper\Email;
use Branch8\Refund\Helper\RefundOperation;
use Branch8\Refund\Model\ResourceModel\SalesRefund\CollectionFactory;
use Branch8\Refund\Model\SalesRefund\Status;
use Exception;
use Magento\Framework\App\State;

/**
 * Cron: batch execute pending refund requests against CTBC after credit memo / invoice readiness.
 */
class RefundScheduleExecute
{
    public const RETRY_TIMES = 100;

    private const LOG_CLASS_KEY = 'RefundScheduleExecute';

    /** @var CollectionFactory */
    protected $refundCollection;

    /** @var State */
    protected $state;

    /** @var Api */
    protected $api;

    /** @var mixed */
    protected $refundResult;

    /** @var RefundOperation */
    protected $refundOperation;

    /** @var array<int, string> */
    protected $abnormalOrderList = [];

    /** @var Email */
    protected $email;

    /** @var CreditmemoRefundData */
    protected $creditmemoRefundData;

    /** @var OrderManagement */
    protected $orderManagement;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param Api $api CTBC API facade
     * @param State $state Application area state
     * @param CollectionFactory $refundCollection Sales refund collection factory
     * @param RefundOperation $refundOperation Refund execution helper
     * @param Email $email Refund notification mailer
     * @param CreditmemoRefundData $creditmemoRefundData Credit memo sidecar data
     * @param OrderManagement $orderManagement HotaiPay parent order helper
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        Api $api,
        State $state,
        CollectionFactory $refundCollection,
        RefundOperation $refundOperation,
        Email $email,
        CreditmemoRefundData $creditmemoRefundData,
        OrderManagement $orderManagement,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->state = $state;
        $this->api = $api;
        $this->refundCollection = $refundCollection;
        $this->refundOperation = $refundOperation;
        $this->email = $email;
        $this->creditmemoRefundData = $creditmemoRefundData;
        $this->orderManagement = $orderManagement;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Run scheduled refund attempts grouped by parent order.
     *
     * @return void
     */
    public function execute()
    {
        $this->refundLogger->log(self::LOG_CLASS_KEY, '------Start Of Cron Refund Execution-----');

        $refundCollection = $this->getRefundCollectionList();

        foreach ($refundCollection as $key => $order) {
            $this->refundLogger->log(self::LOG_CLASS_KEY, 'Start Execute Order Id: ' . $key);

            try {
                $parentOrder = $this->orderManagement->getParentOrderByParentId($key);
                $parentOrderDetail = $parentOrder->getDetail();

                if ($parentOrderDetail->getPaymentMethod() == 'checkmo') {
                    $this->updateRefundChildOrder(
                        $refundCollection[$key]['childRefundData'],
                        Status::CANNOT_REFUND,
                        true
                    );

                    $this->refundLogger->log(self::LOG_CLASS_KEY, 'CheckMo Type. Mark as Cannot Refund.');

                    $this->abnormalOrderList[] = $order['incrementId'];
                    continue;
                }

                if ((int) $order['amount'] < 1) {
                    $this->updateRefundChildOrder(
                        $refundCollection[$key]['childRefundData'],
                        Status::CANNOT_TRIGGERED,
                        true
                    );

                    $this->refundLogger->log(self::LOG_CLASS_KEY, 'Refund Request Amount < 1. Skip.');

                    continue;
                }

                $executionResult = $this->refundOperation->execute(
                    $key,
                    $order['amount'],
                    false
                );

                try {
                    $this->refundLogger->log(
                        self::LOG_CLASS_KEY,
                        '[Refund Result]' . json_encode($executionResult, JSON_UNESCAPED_UNICODE)
                    );
                } catch (Exception $e) {
                    $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'log executionResult');
                }

                if ((int) $order['amount'] < 1) {
                    $this->updateRefundChildOrder(
                        $refundCollection[$key]['childRefundData'],
                        true
                    );

                    $this->refundLogger->log(
                        self::LOG_CLASS_KEY,
                        'Refund Request Amount < 1. No need to request but updated.'
                    );
                    continue;
                }

                if ($executionResult != $this->refundOperation::REFUND_SUCCESS) {
                    $refundCollection[$key]['systemErrorMsg'] = $executionResult;

                    if ((int) $order['retry_times'] >= self::RETRY_TIMES) {
                        $this->abnormalOrderList[] = $order['incrementId'];
                    }

                    continue;
                }
            } catch (\Exception $e) {
                $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'execute loop');

                $refundCollection[$key]['systemErrorMsg'] = $e->getMessage();

                if ((int) $order['retry_times'] >= self::RETRY_TIMES) {
                    $this->abnormalOrderList[] = $order['incrementId'];
                }
                continue;
            }

            $this->updateRefundChildOrder(
                $refundCollection[$key]['childRefundData']
            );
        }

        if ($this->abnormalOrderList) {
            $this->notifyAdminAbnormalOrderList();
        }

        $this->refundLogger->log(self::LOG_CLASS_KEY, '------End Of Cron Refund Execution-----');
    }

    /**
     * Update child refund rows after trigger attempt.
     *
     * @param array<int, mixed> $childRefundData SalesRefund models
     * @param string|bool $status New status or legacy skip flag
     * @param bool $skip When true, write skip comment instead of success comment
     * @return void
     */
    public function updateRefundChildOrder($childRefundData, $status = Status::TRIGGERED, $skip = false)
    {
        foreach ($childRefundData as $order) {
            $requestPayload = json_decode($order->getRequestPayload(), true);

            $order->setStatus($status);
            $order->setIsTriggered(true);
            $order->setTriggeredAt(new \DateTime());
            $order->save();

            try {
                if ($skip) {
                    $this->updateOrderSkipComment($order, $requestPayload);
                    continue;
                }

                $this->updateOrderComment($order, $requestPayload);
            } catch (Exception $e) {
                $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'updateRefundChildOrder');
            }
        }
    }

    /**
     * Add order comment from CTBC current state after successful trigger.
     *
     * @param mixed $order SalesRefund model
     * @param array<string, mixed> $requestPayload Decoded request_payload JSON
     * @return void
     */
    protected function updateOrderComment($order, $requestPayload)
    {
        $orderId = $requestPayload['orderId'];
        $isChildOrder = $requestPayload['isChildOrder'];

        $currentStateLabel = $this->refundOperation->getCurrentStateLabel();
        $comment = __('Refund Status From CTBC changes to %1', $currentStateLabel);

        $this->refundLogger->log(self::LOG_CLASS_KEY, 'CurrentState Update to: ' . $currentStateLabel);

        if ($isChildOrder) {
            $this->refundOperation->updateChildOrderStatus($orderId, $comment);

            return;
        }

        $parentOrder = $this->orderManagement->getParentOrderDetailByParentId($orderId);
        $this->refundOperation->updateOrderComment($parentOrder, $comment);
    }

    /**
     * Email admin when some parent orders exceeded retry threshold.
     *
     * @return void
     */
    protected function notifyAdminAbnormalOrderList()
    {
        try {
            $emailTemplateId = Email::REFUND_ABNORMAL_EMAIL_TEMPLATE;

            $this->email->setEmailTemplateId($emailTemplateId);
            $this->email->setEmailReceivers($this->email->getAbnormalAdminReceviers());
            $this->email->setEmailVars(
                [
                    'Parent IncrementIds' => implode(',', $this->abnormalOrderList),
                ]
            );

            $this->email->send();
        } catch (\Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'notifyAdminAbnormalOrderList');
        }
    }

    /**
     * Build grouped refund queue keyed by parent id.
     *
     * @return array<int|string, array<string, mixed>>
     */
    protected function getRefundCollectionList()
    {
        $collection = $this->refundCollection->create()
            ->addFieldToFilter(
                'is_refunded',
                false
            )->addFieldToFilter(
                'is_triggered',
                false
            )->addFieldToFilter(
                'status',
                Status::PENDING
            )->addFieldToFilter(
                'memo_id',
                ['neq' => 'NULL']
            );

        $preparedCollection = [];

        foreach ($collection as $value) {
            if (!$this->creditmemoRefundData->IsInvoiceProcessDone($value->getMemoId())) {
                $recordId = $value->getSalesRefundId();
                $this->refundLogger->log(
                    self::LOG_CLASS_KEY,
                    "Record Id: $recordId - Invoice is not ready."
                );
                continue;
            }

            $retry = $value->getRefundTriggeredRetryCount();

            if ((int) $retry >= self::RETRY_TIMES) {
                $value->setStatus(Status::CANNOT_TRIGGERED);
                $value->save();

                $this->abnormalOrderList[] = $value->getIncrementId();
                continue;
            }

            $retry = (int) $retry + 1;
            $value->setRefundTriggeredRetryCount($retry);
            $value->save();

            $parentId = $value->getParentOrderId();

            $preparedCollection[$parentId]['amount'] = $preparedCollection[$parentId]['amount'] ?? 0;
            $preparedCollection[$parentId]['amount'] = $preparedCollection[$parentId]['amount'] + $value->getRefundAmount();

            $preparedCollection[$parentId]['childRefundData'][] = $value;

            $preparedCollection[$parentId]['incrementId'] = $value->getIncrementId();
            $preparedCollection[$parentId]['retry_times'] = $retry;
        }

        return $preparedCollection;
    }

    /**
     * Comment path when refund is skipped (amount / payment rules).
     *
     * @param mixed $order SalesRefund model
     * @param array<string, mixed> $requestPayload Decoded request_payload JSON
     * @return void
     */
    protected function updateOrderSkipComment($order, $requestPayload)
    {
        $orderId = $requestPayload['orderId'];
        $isChildOrder = $requestPayload['isChildOrder'];

        $comment = __('Refund Request Amount <= 0 or Payment Cannot Refund.');

        if ($isChildOrder) {
            $this->refundOperation->updateChildOrderStatus($orderId, $comment);

            return;
        }

        $parentOrder = $this->orderManagement->getParentOrderDetailByParentId($orderId);
        $this->refundOperation->updateOrderComment($parentOrder, $comment);
    }
}
