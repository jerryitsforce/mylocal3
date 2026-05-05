<?php
declare(strict_types=1);

namespace Branch8\WebkulMpRmaSystem\Rewrite\Webkul\MpRmaSystem\Controller\Rewrite\Order;


use Magento\Framework\Exception\LocalizedException;

class Invoice extends \Webkul\MpRmaSystem\Controller\Rewrite\Order\Invoice
{

    protected function doInvoiceExecution($order): void
    {
        try {
            $sellerId = $this->_customerSession->getCustomerId();
            $orderId = $order->getId();
            if ($order->canUnhold()) {
                $this->messageManager->addError(
                    __('Can not create invoice as order is in HOLD state')
                );
            } else {
                $data = [];
                $data['send_email'] = 1;
                $marketplaceOrder = $this->orderHelper->getOrderinfo($orderId);
                $invoiceId = $marketplaceOrder->getInvoiceId();
                $data = $this->getRequest()->getPost('invoice');
                $orderId = $order->getId();
                $invoiceData = $this->getRequest()->getParam('invoice', []);
                $invoiceItems = isset($invoiceData['items']) ? $invoiceData['items'] : [];
                if (!$invoiceId) {
                    $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->load($orderId);
                    if (!$order->getId()) {
                        throw new LocalizedException(__('The order no longer exists.'));
                    }
                    if (!$order->canInvoice()) {
                        throw new LocalizedException(
                            __('The order does not allow an invoice to be created.')
                        );
                    }
                    $invoice = $this->invoiceService->prepareInvoice($order, $invoiceItems);

                    if (!$invoice->getTotalQty()) {
                        throw new LocalizedException(
                            __("The invoice can't be created without products. Add products and try again.")
                        );
                    }
                    $this->_coreRegistry->register('current_invoice', $invoice);
                    if (!empty($data['comment_text'])) {
                        $invoice->addComment(
                            $data['comment_text'],
                            isset($data['comment_customer_notify']),
                            isset($data['is_visible_on_front'])
                        );

                        $invoice->setCustomerNote($data['comment_text']);
                        $invoice->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                    }
                    $invoice->register();
                    $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                    $invoice->getOrder()->setIsInProcess(true);
                    $transactionSave = $this->_objectManager->create(
                        \Magento\Framework\DB\Transaction::class
                    )->addObject(
                        $invoice
                    )->addObject(
                        $invoice->getOrder()
                    );
                    if (!empty($data['do_shipment']) || (int)$invoice->getOrder()->getForcedShipmentWithInvoice()) {
                        $shipment = $this->_prepareShipment($invoice);
                        if ($shipment) {
                            $transactionSave->addObject($shipment);
                        }
                    }
                    $transactionSave->save();
                    $invoiceId = $invoice->getId();
                    $this->_invoiceSender->send($invoice);
                    $this->messageManager->addSuccess(
                        __('Invoice has been created for this order.')
                    );
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->helper->logDataInLogger(
                "Controller_Order_Invoice doInvoiceExecution : " . $e->getMessage()
            );
            $this->messageManager->addError(
                __('We can\'t save the invoice right now.')
            );
            $this->messageManager->addError($e->getMessage());
        }
    }
    /**
     * Prepare shipment
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Magento\Sales\Model\Order\Shipment|false
     */
    protected function _prepareShipment($invoice)
    {
        $data = $this->getRequest()->getParam('invoice');
        $itemArr = [];
        if (!isset($data['items']) || empty($data['items'])) {
            $orderItems = $invoice->getOrder()->getItems();
            foreach ($orderItems as $item) {
                $itemArr[$item->getId()] = (int)$item->getQtyOrdered();
            }
        }
        $shipment = $this->_shipmentFactory->create(
            $invoice->getOrder(),
            isset($data['items']) ? $data['items'] : $itemArr,
            $this->getRequest()->getPost('tracking')
        );
        if (!$shipment->getTotalQty()) {
            return false;
        }

        return $shipment->register();
    }
    public function execute()
    {

        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            if ($order = $this->_initOrder()) {
                $this->doInvoiceExecution($order);
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/view',
                    [
                        'id' => $order->getEntityId(),
                        '_secure' => $this->getRequest()->isSecure(),
                    ]
                );
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/history',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
