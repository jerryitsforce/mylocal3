<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Refund\Observer;

use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Refund\Helper\CancelTicket;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\CreateCreditMemo;
use Branch8\Refund\Helper\RefundOperation;
use Branch8\Rma\Helper\RmaRecord;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Handles post-cancel flow: invoice tag, ticket cancel, logging and item flow status.
 */
class CancelObserver implements ObserverInterface
{
    public const ECPAY_INVOICE_CANCEL = 3;

    private const LOG_CLASS_KEY = 'CancelObserver';

    /** @var RefundOperation */
    protected $refundOperation;

    /** @var CreateCreditMemo */
    protected $createCreditMemo;

    /** @var RmaRecord */
    protected $rmaRecord;

    /** @var UpdateOrderStatus */
    protected $updateOrderStatus;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /** @var CancelTicket */
    protected CancelTicket $cancelTicket;

    /**
     * @param RefundOperation $refundOperation Refund operation helper
     * @param CreateCreditMemo $createCreditMemo Credit memo factory helper
     * @param RmaRecord $rmaRecord RMA persistence helper
     * @param UpdateOrderStatus $updateOrderStatus Order item status audit
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     * @param CancelTicket $cancelTicket Ticket cancellation helper
     */
    public function __construct(
        RefundOperation $refundOperation,
        CreateCreditMemo $createCreditMemo,
        RmaRecord $rmaRecord,
        UpdateOrderStatus $updateOrderStatus,
        ConfigurableRefundLogger $refundLogger,
        CancelTicket $cancelTicket
    ) {
        $this->refundOperation = $refundOperation;
        $this->createCreditMemo = $createCreditMemo;
        $this->rmaRecord = $rmaRecord;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->refundLogger = $refundLogger;
        $this->cancelTicket = $cancelTicket;
    }

    /**
     * Observer for order_cancel_after: mark invoice, cancel tickets, set final status.
     *
     * @param Observer $observer Event observer with order
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $status = $order->getOrigData('status');

        $this->refundLogger->log(self::LOG_CLASS_KEY, '[Cancel Order Id] ' . $order->getId());

        $order->setEcpayInvoiceAutoTag(self::ECPAY_INVOICE_CANCEL);
        $order->save();

        $this->cancelTicket->execute($order);

        $cannorRefundStatus = [
            Status::STATUS_PENDING,
            Status::STATUS_PAYMENT_REVIEW,
            Status::STATUS_PENDING_PAYMENT,
        ];

        $invoiceId = $order->getInvoiceCollection()->getFirstItem()->getId();

        $this->refundLogger->log(
            self::LOG_CLASS_KEY,
            '[Cancel Order Id] ' . $order->getId() . ' - Is Paid: ' . $order->getData('is_paid') . ' - Invoice Id: ' . $invoiceId
        );

        $comment = __('Canceled Order.');

        if (!$invoiceId || in_array($status, $cannorRefundStatus) || !$order->getData('is_paid')) {
            $this->refundLogger->log(self::LOG_CLASS_KEY, '[Cancel Status] There is no payment, cannot refund.');

            $comment = __('Canceled Order. There is no invoice or status not allow to refund, so refunding is not necessary.');
        }

        $order->setState(State::STATE_CANCELED);
        $order->setStatus(Status::STATUS_CANCELED);
        if (!$order->getIsVirtual()) {
            $order->setIsForceUpdateStatus(true);
        }

        $order->addCommentToStatusHistory($comment);
        $order->save();

        $this->updateItemStatus($order, Status::STATUS_CANCELED);

        return $this;
    }

    /**
     * Update each order item flow status and audit trail.
     *
     * @param \Magento\Sales\Model\Order $order Order entity
     * @param string $status Target flow status code
     * @return void
     */
    protected function updateItemStatus($order, $status)
    {
        try {
            foreach ($order->getAllItems() as $item) {
                $item->setFlowStatus($status);
                $item->save();

                $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, $status);
            }
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'updateItemStatus');
        }
    }
}
