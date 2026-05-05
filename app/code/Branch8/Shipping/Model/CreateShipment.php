<?php

declare(strict_types=1);

namespace Branch8\Shipping\Model;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Shipping\Model\ShipmentNotifier;

class CreateShipment
{
    /**
     * @var ShipmentFactory
     */
    private ShipmentFactory $shipmentFactory;

    /**
     * @var ShipmentNotifier
     */
    private ShipmentNotifier $shipmentNotifier;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var TransactionFactory
     */
    private TransactionFactory $transactionFactory;

    /**
     * CreateShipment constructor.
     *
     * @param ShipmentFactory $shipmentFactory
     * @param ShipmentNotifier $shipmentNotifier
     */
    public function __construct(
        ShipmentFactory          $shipmentFactory,
        ShipmentNotifier         $shipmentNotifier,
        OrderRepositoryInterface $orderRepository,
        TransactionFactory       $transactionFactory
    )
    {
        $this->shipmentFactory = $shipmentFactory;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->orderRepository = $orderRepository;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param int $orderId
     * @return Shipment
     * @throws LocalizedException
     */
    public function execute(int $orderId): Shipment
    {
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $this->orderRepository->get($orderId);
        if (!$order->canShip()) {
            throw new LocalizedException(__('Cannot do shipment for the order.'));
        }
        $shipmentItems = $this->getQuantitiesFromOrderItems($order->getItems());
        /** @var Shipment $shipment */
        $shipment = $this->shipmentFactory->create($order, $shipmentItems);
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        try {
            $this->shipmentNotifier->notify($shipment);
        } catch (MailException $e) {
            // do nothing
        }
        // Disable shipment save for issue duplicate inventory_reservation when create shipment
        //$shipment->save();
        return $shipment;
    }

    /**
     * Returns quantities from order items.
     *
     * @param OrderItemInterface[] $items
     *
     * @return int[]
     */
    private function getQuantitiesFromOrderItems(array $items): array
    {
        $shipmentItems = [];
        foreach ($items as $item) {
            if (!$item->getIsVirtual() && (!$item->getParentItem() || $item->isShipSeparately())) {
                $shipmentItems[$item->getItemId()] = $item->getQtyOrdered();
            }
        }
        return $shipmentItems;
    }
}
