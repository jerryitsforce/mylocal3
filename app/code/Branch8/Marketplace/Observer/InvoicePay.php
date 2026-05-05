<?php

namespace Branch8\Marketplace\Observer;

use Branch8\Marketplace\Service\MarketplaceLogger;

class InvoicePay implements \Magento\Framework\Event\ObserverInterface{

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $_convertOrder;
    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    protected $_shipmentNotifier;

    protected $transactionFactory;
    private MarketplaceLogger $marketplaceLogger;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        MarketplaceLogger $marketplaceLogger
    ){
        $this->_orderRepository = $orderRepository;
        $this->_convertOrder = $convertOrder;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->transactionFactory = $transactionFactory;
        $this->marketplaceLogger = $marketplaceLogger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        if (!$order->canShip()) {
            return;
        }
        $orderShipment = $this->_convertOrder->toShipment($order);
        foreach ($order->getAllVisibleItems() as $orderItem) {
            // Check virtual item and item Quantity
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            $shipment = clone $orderShipment;
            try {
                $qty = $orderItem->getQtyToShip();
                $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                $shipment->addItem($shipmentItem);

                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                // Save created Order Shipment
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();

                // Send Shipment Email
                $this->_shipmentNotifier->notify($shipment);
                $shipment->save();
            } catch (\Exception $e) {
                $this->marketplaceLogger->logException('InvoicePay', $e, [
                    'order_id' => $order->getId(),
                    'invoice_id' => $invoice->getId(),
                ]);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }

        }


    }
}