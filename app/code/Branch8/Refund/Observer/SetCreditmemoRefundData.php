<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Refund\Observer;

use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Sales\Model\CreditMemo\FinancialReviewStatus;
use Branch8\Sales\Model\CreditMemo\VoidInvoiceType;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Item;

/**
 * On credit memo refund: financial review flag, void/reissue invoice type, refund points per line.
 */
class SetCreditmemoRefundData implements ObserverInterface
{
    private const LOG_CLASS_KEY = 'SetCreditmemoRefundData';

    /** @var Item */
    protected $orderItem;

    /** @var ConfigurableRefundLogger */
    private ConfigurableRefundLogger $refundLogger;

    /**
     * @param Item $orderItem Order item model (legacy DI)
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        Item $orderItem,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->orderItem = $orderItem;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Observer: sales_order_creditmemo_refund — enrich memo before persistence.
     *
     * @param Observer $observer Event with creditmemo and order
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $observer->getEvent()->getOrder();

        if ($creditmemo instanceof Creditmemo) {
            $order = $creditmemo->getOrder();
        }

        $customerIdentifier = $order->getEcpayInvoiceCustomerIdentifier();
        if ($customerIdentifier) {
            $creditmemo->setFinancialReviewStatus(FinancialReviewStatus::FINANCIAL_REVIEWING);
        }

        $voidInvoiceType = $this->getVoidInvoiceType($creditmemo);
        $creditmemo->setVoidInvoiceType($voidInvoiceType);

        $this->refundLogger->log(
            self::LOG_CLASS_KEY,
            [
                'order_id' => $order->getId(),
                'creditmemo_id' => $creditmemo->getId(),
                'issue_type' => $creditmemo->getIssueType(),
                'void_invoice_type' => $voidInvoiceType,
                'has_customer_identifier' => (bool) $customerIdentifier,
            ]
        );

        $order = $observer->getEvent()->getOrder();

        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            $totalPointUsed = $orderItem->getRowTotalPointUsed();
            $creditmemoItem->setRefundPoint($totalPointUsed);
        }

        return $this;
    }

    /**
     * Map cancel/return and same-month rules to VoidInvoiceType.
     *
     * @param Creditmemo $creditmemo Credit memo
     * @return int Void invoice type constant
     */
    protected function getVoidInvoiceType($creditmemo)
    {
        $voidInvoiceType = VoidInvoiceType::DEFAULT;

        $isSameMonth = true;
        $order = $creditmemo->getOrder();
        $invoice = $creditmemo->getInvoice();

        $createdAt = $this->convertDate(
            $invoice ? $invoice->getCreatedAt() : $order->getCreatedAt()
        );

        $currentDate = $this->convertDate('now');

        if ($createdAt != $currentDate) {
            $isSameMonth = false;
        }

        if ($creditmemo->getIssueType() == \Branch8\Sales\Model\CreditMemo\IssueType::CANCEL) {
            $voidInvoiceType = VoidInvoiceType::ALLOWENCE;

            if ($isSameMonth) {
                $voidInvoiceType = VoidInvoiceType::VOID;
            }
        }

        if ($creditmemo->getIssueType() == \Branch8\Sales\Model\CreditMemo\IssueType::RETURN) {
            $order = $creditmemo->getOrder();

            $voidInvoiceType = VoidInvoiceType::VOID_AND_ISSUE_OFF_INVOICE_ALLOWENCE;

            if ($isSameMonth) {
                $voidInvoiceType = VoidInvoiceType::VOID_AND_REISSUE;
            }

            if ($this->checkOrderItemIsAllRefunded($order)) {
                $voidInvoiceType = VoidInvoiceType::ALLOWENCE;

                if ($isSameMonth) {
                    $voidInvoiceType = VoidInvoiceType::VOID;
                }
            }
        }

        return $voidInvoiceType;
    }

    /**
     * True when all visible items are fully refunded and invoiced totals net to zero.
     *
     * @param \Magento\Sales\Model\Order $order Order entity
     * @return bool
     */
    private function checkOrderItemIsAllRefunded($order)
    {
        try {
            if (((int) $order->getTotalInvoiced() - (int) $order->getTotalRefunded()) == 0) {
                foreach ($order->getAllVisibleItems() as $item) {
                    if ((int) $item->getQtyRefunded() == 0) {
                        return false;
                    }
                }

                return true;
            }
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'checkOrderItemIsAllRefunded');

            throw new Exception(__($e->getMessage()));
        }

        return false;
    }

    /**
     * Normalize datetime to Y-m in Asia/Taipei for same-month checks.
     *
     * @param string $dt UTC or relative datetime string
     * @return string Year-month key
     */
    protected function convertDate($dt)
    {
        $date = new \DateTime($dt, new \DateTimeZone('UTC'));

        $tpTimeZone = new \DateTimeZone('Asia/Taipei');
        $date->setTimeZone($tpTimeZone);

        return $date->format('Y-m');
    }
}
