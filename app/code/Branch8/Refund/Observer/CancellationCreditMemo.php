<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Refund\Observer;

use Branch8\Refund\Helper\CancelTicket;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\CreateCreditMemo;
use Branch8\Refund\Helper\RefundOperation;
use Branch8\Refund\Helper\RmaRefundData;
use Branch8\Rma\Helper\RmaRecord;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * Observer for sales_order_cancel_issue_creditmemo: create memo, link RMA, cancel tickets.
 */
class CancellationCreditMemo implements ObserverInterface
{
    public const ECPAY_INVOICE_CANCEL = 3;

    private const LOG_CLASS_KEY = 'CancellationCreditMemo';

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

    /** @var OrderRepository */
    private OrderRepository $orderRepository;

    /** @var RmaRefundData */
    private $rmaRefundData;

    /**
     * @param RefundOperation $refundOperation Refund operation helper
     * @param CreateCreditMemo $createCreditMemo Credit memo helper
     * @param RmaRecord $rmaRecord RMA helper
     * @param UpdateOrderStatus $updateOrderStatus Item status helper
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     * @param CancelTicket $cancelTicket Ticket cancel helper
     * @param OrderRepository $orderRepository Order repository
     * @param RmaRefundData $rmaRefundData RMA refund sidecar data
     */
    public function __construct(
        RefundOperation $refundOperation,
        CreateCreditMemo $createCreditMemo,
        RmaRecord $rmaRecord,
        UpdateOrderStatus $updateOrderStatus,
        ConfigurableRefundLogger $refundLogger,
        CancelTicket $cancelTicket,
        OrderRepository $orderRepository,
        RmaRefundData $rmaRefundData
    ) {
        $this->refundOperation = $refundOperation;
        $this->createCreditMemo = $createCreditMemo;
        $this->rmaRecord = $rmaRecord;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->refundLogger = $refundLogger;
        $this->cancelTicket = $cancelTicket;
        $this->orderRepository = $orderRepository;
        $this->rmaRefundData = $rmaRefundData;
    }

    /**
     * Observer entry: sales_order_cancel_issue_creditmemo.
     *
     * @param Observer $observer Event with order and rmaId
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $rmaId = $observer->getEvent()->getData('rmaId');

        $this->refundLogger->log(self::LOG_CLASS_KEY, '[Cancel Order Id] ' . $order->getId());

        $order->setEcpayInvoiceAutoTag(self::ECPAY_INVOICE_CANCEL);
        $order->save();

        $this->cancelTicket->execute($order);

        $result = $this->createCreditMemo->execute(
            $order,
            \Branch8\Sales\Model\CreditMemo\IssueType::CANCEL,
            true
        );

        if ($result['error'] == 1) {
            throw new \Magento\Framework\Exception\LocalizedException(__($result['msg']));
        }

        $order = $this->createCreditMemo->updateOrderByCreditMemo($result['memo'], $order, false);
        $this->orderRepository->save($order);

        $this->rmaRefundData->updateRmaCreditmemo(
            $rmaId,
            ['memo_id' => $result['memo_id']]
        );

        return $this;
    }
}
