<?php

declare(strict_types=1);

namespace Branch8\Sales\Rewrite\Model\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\OrderStateResolverInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class Payment extends Order\Payment
{
    private $orderStateResolver;

    /**
     * @inheritdoc
     */
    public function refund($creditmemo)
    {
        $baseAmountToRefund = $this->formatAmount($creditmemo->getBaseGrandTotal());
        $this->setTransactionId(
            $this->transactionManager->generateTransactionId($this, TransactionInterface::TYPE_REFUND)
        );
        $isOnline = false;
        $gateway = $this->getMethodInstance();
        $invoice = null;
        if ($gateway->canRefund()) {
            $this->setCreditmemo($creditmemo);
            if ($creditmemo->getDoTransaction()) {
                $invoice = $creditmemo->getInvoice();
                if ($invoice) {
                    $isOnline = true;
                    $captureTxn = $this->transactionRepository->getByTransactionId(
                        $invoice->getTransactionId(),
                        $this->getId(),
                        $this->getOrder()->getId()
                    );
                    if ($captureTxn) {
                        $this->setTransactionIdsForRefund($captureTxn);
                    }
                    $this->setShouldCloseParentTransaction(true);
                    // TODO: implement multiple refunds per capture
                    try {
                        $gateway->setStore(
                            $this->getOrder()->getStoreId()
                        );
                        $this->setRefundTransactionId($invoice->getTransactionId());
                        $gateway->refund($this, $baseAmountToRefund);

                        $creditmemo->setTransactionId($this->getLastTransId());
                    } catch (LocalizedException $e) {
                        if (!$captureTxn) {
                            throw new LocalizedException(
                                __('If the invoice was created offline, try creating an offline credit memo.'),
                                $e
                            );
                        }
                        throw $e;
                    }
                }
            } elseif ($gateway->isOffline()) {
                $gateway->setStore(
                    $this->getOrder()->getStoreId()
                );
                $gateway->refund($this, $baseAmountToRefund);
            }
        }

        // update self totals from creditmemo
        $this->_updateTotals(
            [
                'amount_refunded' => $creditmemo->getGrandTotal(),
                'base_amount_refunded' => $baseAmountToRefund,
                'base_amount_refunded_online' => $isOnline ? $baseAmountToRefund : null,
                'shipping_refunded' => $creditmemo->getShippingAmount(),
                'base_shipping_refunded' => $creditmemo->getBaseShippingAmount(),
            ]
        );

        // update transactions and order state
        $transaction = $this->addTransaction(
            TransactionInterface::TYPE_REFUND,
            $creditmemo,
            $isOnline
        );
        if ($invoice) {
            $message = __('We refunded %1 online.', $this->formatPrice($baseAmountToRefund));
        } else {
            $message = $this->hasMessage() ? $this->getMessage() : __(
                'We refunded %1 offline.',
                $this->formatPrice($baseAmountToRefund)
            );
        }
        $message = $this->prependMessage($message);
        $message = $this->_appendTransactionToMessage($transaction, $message);
        $order = $this->getOrder();
        $currentState = $order->getState();
        $currentStatus = $order->getStatus();
        $order = $this->getOrder() ?? $order;
        if ($order->getIsForceUpdateStatus() || $currentState !== Order::STATE_PROCESSING) {
            $orderState = $this->getOrderStateResolver()->getStateForOrder($order);
            $statuses = $order->getConfig()->getStateStatuses($orderState, false);
            $status = $order->getStatus();
            if($status == Order::STATE_CLOSED) {
                $status = Order::STATE_COMPLETE;
            }
            $order->addStatusHistoryComment($message, $status)
                ->setIsCustomerNotified($creditmemo->getOrder()->getCustomerNoteNotify());
        } else {
            $order->addStatusHistoryComment($message, $currentStatus)
                ->setIsCustomerNotified($creditmemo->getOrder()->getCustomerNoteNotify());
        }
        $this->_eventManager->dispatch(
            'sales_order_payment_refund',
            ['payment' => $this, 'creditmemo' => $creditmemo]
        );

        return $this;
    }

    /**
     * Returns OrderStateResolver object.
     *
     * @return OrderStateResolverInterface
     */
    private function getOrderStateResolver()
    {
        if ($this->orderStateResolver === null) {
            $this->orderStateResolver = ObjectManager::getInstance()->get(OrderStateResolverInterface::class);
        }

        return $this->orderStateResolver;
    }

    /**
     * Set payment parent transaction id and current transaction id if it not set
     *
     * @param Transaction $transaction
     * @return void
     */
    private function setTransactionIdsForRefund(Transaction $transaction)
    {
        if (!$this->getTransactionId()) {
            $this->setTransactionId(
                $this->transactionManager->generateTransactionId(
                    $this,
                    TransactionInterface::TYPE_REFUND,
                    $transaction
                )
            );
        }
        $this->setParentTransactionId($transaction->getTxnId());
    }
}
