<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Refund\Plugin;

use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\RefundOperation;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Plugin on credit memo refund: queue CTBC refund and tidy auto-generated status comments.
 */
class Creditmemo
{
    private const LOG_CLASS_KEY = 'Creditmemo';

    /** @var RefundOperation */
    protected $refundOperation;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var string|null */
    private $paymentMethod;

    /** @var mixed */
    protected $refundRecord;

    /** @var ConfigurableRefundLogger */
    private ConfigurableRefundLogger $refundLogger;

    /**
     * @param RefundOperation $refundOperation Refund schedule helper
     * @param OrderRepositoryInterface $orderRepository Order repository
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        RefundOperation $refundOperation,
        OrderRepositoryInterface $orderRepository,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->refundOperation = $refundOperation;
        $this->orderRepository = $orderRepository;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Before refund: validate amount, optionally cancel order state, enqueue CTBC refund row.
     *
     * @param CreditmemoManagementInterface $subject Intercepted service
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo Credit memo entity
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeRefund(CreditmemoManagementInterface $subject, $creditmemo)
    {
        $orderId = (int) $creditmemo->getOrderId();
        $amount = $creditmemo->getBaseGrandTotal();

        if ($amount != (int) $amount) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Refund amount Need be Type Int.')
            );
        }

        $order = $creditmemo->getOrder();
        $this->paymentMethod = $order->getPayment()->getMethod();

        try {
            if ($creditmemo->getIssueType() == \Branch8\Sales\Model\CreditMemo\IssueType::CANCEL) {
                $order->setState(\Branch8\HotaiCore\Model\Order\State::STATE_CANCELED);
                $order->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_CANCELED);

                $order->save();
            }

            $refundRecord = $this->refundOperation->setToScheduleList($orderId, $amount);

            if (!$refundRecord) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($this->refundOperation::REFUND_ERROR_MSG)
                );
            }

            $this->setRecord($refundRecord);
        } catch (\Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'beforeRefund');

            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * After refund: strip default Magento refund comment and link memo to SalesRefund row.
     *
     * @param CreditmemoManagementInterface $subject Intercepted service
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $result Persisted credit memo
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     */
    public function afterRefund(CreditmemoManagementInterface $subject, $result)
    {
        $order = $this->orderRepository->get((int) $result->getOrderId());
        $histories = $this->normalizeStatusHistories($order);
        $removed = $this->removeMagentoRefundStatusComment($histories);
        if ($removed) {
            $order->setStatusHistories(array_values($histories));
        }

        $needReview = !is_null($order->getEcpayInvoiceCustomerIdentifier()) ? true : false;

        $this->refundRecord->setMemoId($result->getId());
        $this->refundRecord->setIsFinancialReviewing($needReview);
        $this->refundRecord->save();

        $order->setHasRefund(true);
        $order->addStatusHistoryComment(
            __('Schedule Sending Refund Request to CTBC'),
            $order->getStatus()
        )->setIsCustomerNotified(false);
        $order->save();

        return $result;
    }

    /**
     * @param mixed $refundRecord SalesRefund model
     * @return void
     */
    private function setRecord($refundRecord)
    {
        $this->refundRecord = $refundRecord;
    }

    /**
     * @param \Magento\Sales\Model\Order $order Order entity
     * @return array<int, \Magento\Sales\Model\Order\Status\History>
     */
    private function normalizeStatusHistories($order): array
    {
        $histories = $order->getStatusHistories();
        if ($histories === null) {
            return [];
        }
        if (is_array($histories)) {
            return array_values($histories);
        }
        if ($histories instanceof \Traversable) {
            return array_values(iterator_to_array($histories));
        }

        return [];
    }

    /**
     * Remove Magento's automatic "We refunded..." style history row when appropriate.
     *
     * @param array<int, \Magento\Sales\Model\Order\Status\History> $histories Mutable history list
     * @return bool True when a row was removed
     */
    private function removeMagentoRefundStatusComment(array &$histories): bool
    {
        for ($i = count($histories) - 1; $i >= 0; $i--) {
            $text = strip_tags((string) $histories[$i]->getComment());
            if ($this->isMagentoAutoRefundStatusComment($text)) {
                unset($histories[$i]);
                return true;
            }
        }

        if ($histories !== []) {
            $lastText = strip_tags((string) $histories[array_key_last($histories)]->getComment());
            $isCreatorLog = stripos($lastText, 'Credit memo') !== false
                && stripos($lastText, 'created by') !== false;
            if (!$isCreatorLog) {
                array_pop($histories);
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $text Plain comment text
     * @return bool
     */
    private function isMagentoAutoRefundStatusComment(string $text): bool
    {
        if ($text === '') {
            return false;
        }
        if (stripos($text, 'Credit memo') !== false && stripos($text, 'created by') !== false) {
            return false;
        }
        if (stripos($text, 'Schedule Sending Refund Request to CTBC') !== false) {
            return false;
        }

        return stripos($text, 'refunded') !== false;
    }
}
