<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Refund\Observer;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Refund\Helper\CancelTicket;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\CreateCreditMemo;
use Branch8\Refund\Helper\RefundOperation;
use Branch8\Rma\Helper\RmaRecord;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * Observer for paid order cancellation: credit memo path or RMA pending path by invoice type.
 */
class CancelPaidOrderObserver implements ObserverInterface
{
    public const ECPAY_INVOICE_CANCEL = 3;

    private const LOG_CLASS_KEY = 'CancelPaidOrderObserver';

    /** @var RefundOperation */
    protected $refundOperation;

    /** @var CreateCreditMemo */
    protected $createCreditMemo;

    /** @var RmaRecord */
    protected $rmaRecord;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /** @var OrderRepository */
    private OrderRepository $orderRepository;

    /** @var CancelTicket */
    protected CancelTicket $cancelTicket;

    /**
     * @param RefundOperation $refundOperation Refund operation helper
     * @param CreateCreditMemo $createCreditMemo Credit memo helper
     * @param RmaRecord $rmaRecord RMA helper
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     * @param OrderRepository $orderRepository Order repository
     * @param CancelTicket $cancelTicket Ticket cancel helper
     */
    public function __construct(
        RefundOperation $refundOperation,
        CreateCreditMemo $createCreditMemo,
        RmaRecord $rmaRecord,
        ConfigurableRefundLogger $refundLogger,
        OrderRepository $orderRepository,
        CancelTicket $cancelTicket
    ) {
        $this->refundOperation = $refundOperation;
        $this->createCreditMemo = $createCreditMemo;
        $this->rmaRecord = $rmaRecord;
        $this->refundLogger = $refundLogger;
        $this->orderRepository = $orderRepository;
        $this->cancelTicket = $cancelTicket;
    }

    /**
     * Observer entry: sales_order_cancel_paied_order.
     *
     * @param Observer $observer Event observer with order
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order->getData('is_paid')) {
            return $this;
        }

        $this->refundLogger->log(self::LOG_CLASS_KEY, '[Cancel Paid Order Id] ' . $order->getId());

        $order->setState(\Branch8\HotaiCore\Model\Order\State::STATE_CANCELED);
        $order->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_CANCELED);

        $customerIdentifier = $order->getEcpayInvoiceCustomerIdentifier();

        if (!$customerIdentifier) {
            $this->cancelTicket->execute($order);

            $order->setEcpayInvoiceAutoTag(self::ECPAY_INVOICE_CANCEL);
            $order->save();

            $result = $this->createCreditMemo->execute(
                $order,
                \Branch8\Sales\Model\CreditMemo\IssueType::CANCEL,
                true
            );

            if ($result['error'] == 1) {
                throw new \Magento\Framework\Exception\LocalizedException(__($result['msg']));
            }

            $order = $this->createCreditMemo->updateOrderByCreditMemo(
                $result['memo'],
                $order,
                false
            );

            $this->updateItemStatus($order, \Branch8\HotaiCore\Model\Order\Status::STATUS_PROCESSING_REFUND);

            $this->orderRepository->save($order);

            return $this;
        }

        $order->setState(\Branch8\HotaiCore\Model\Order\State::STATE_CANCEL_PENDING);
        $order->setStatus(\Branch8\HotaiCore\Model\Order\Status::STATUS_CANCEL_PENDING);
        $order->save();

        $rma['memo_id'] = null;
        $this->rmaRecord->createByCancellation($order, $rma);

        $this->updateItemStatus($order, Status::STATUS_FINANCIAL_REVIEW);

        return $this;
    }

    /**
     * Update order item flow status.
     *
     * @param \Magento\Sales\Model\Order $order Order entity
     * @param string $status Flow status code
     * @return void
     */
    protected function updateItemStatus($order, $status)
    {
        foreach ($order->getAllItems() as $item) {
            $item->setFlowStatus($status);
            $item->save();
        }
    }
}
