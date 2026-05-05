<?php
namespace Branch8\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Sales\Model\Order\Item as OrderItem;

class BlockDuplicateShipmentObserver implements ObserverInterface
{
    /**
     * @var ShipmentCollectionFactory
     */
    protected $shipmentCollectionFactory;

    /**
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     */
    public function __construct(
        ShipmentCollectionFactory $shipmentCollectionFactory
    ) {
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
    }

    /**
     * Validate shipment quantities before saving
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        /**
         *Only check when new shipment
         */
        if (!$shipment->isObjectNew()) {
            return;
        }
        $order = $shipment->getOrder();

        // Check existing shipments for the order
        $existingShipments = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('order_id', $order->getId());

        // Get order items and their shipped quantities
        $orderItems = $order->getAllItems();
        $shippedQuantities = $this->getShippedQuantities($existingShipments);

        if (count($shipment->getAllItems()) === 0) {
            throw new LocalizedException(__('Shipment must have at least one item.'));
        }

        // Validate shipment items' quantities
        foreach ($shipment->getAllItems() as $shipmentItem) {
            $orderItemId = $shipmentItem->getOrderItemId();
            $shipmentQty = $shipmentItem->getQty();

            // Find the corresponding order item
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getId() == $orderItemId) {
                    $orderedQty = $orderItem->getQtyOrdered();
                    $alreadyShippedQty = isset($shippedQuantities[$orderItemId]) ? $shippedQuantities[$orderItemId] : 0;

                    // Calculate remaining quantity available to ship
                    $remainingQty = $orderedQty - $alreadyShippedQty;

                    // Validate shipment quantity
                    if ($shipmentQty > $remainingQty) {
                        throw new LocalizedException(
                            __(
                                "Cannot ship %1 units of '%2'. Only %3 units remain to be shipped.",
                                $shipmentQty,
                                $shipmentItem->getName(),
                                $remainingQty
                            )
                        );
                    }
                    break;
                }
            }
        }
    }

    /**
     * Get total shipped quantities per order item from existing shipments
     *
     * @param ShipmentCollectionFactory $shipments
     * @return array
     */
    protected function getShippedQuantities($shipments)
    {
        $shippedQuantities = [];
        foreach ($shipments as $shipment) {
            foreach ($shipment->getAllItems() as $item) {
                $orderItemId = $item->getOrderItemId();
                $shippedQuantities[$orderItemId] = ($shippedQuantities[$orderItemId] ?? 0) + $item->getQty();
            }
        }
        return $shippedQuantities;
    }
}
