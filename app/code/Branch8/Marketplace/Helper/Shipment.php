<?php

namespace Branch8\Marketplace\Helper;

use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Branch8\Marketplace\Service\MarketplaceLogger;

class Shipment extends \Magento\Framework\App\Helper\AbstractHelper{

    protected $_convertOrder;

    protected $transactionFactory;

    protected $_shipmentNotifier;

    protected $shipmentRepository;

    protected $trackFactory;
    private MarketplaceLogger $marketplaceLogger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        ShipmentTrackInterfaceFactory $trackFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        MarketplaceLogger $marketplaceLogger
//        \Branch8\Marketplace\Helper\Import $importShipmentHelper
    ){
        $this->_convertOrder = $convertOrder;
        $this->transactionFactory = $transactionFactory;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->trackFactory = $trackFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->marketplaceLogger = $marketplaceLogger;
//        $this->importShipmentHelper = $importShipmentHelper;
    }

    public function validateDataForShipment($data){
        if(!isset($data['tracking_number_all']) || !isset($data['carrier_all'])){
            return false;
        }
        if(!isset($data['items'])){
            return false;
        }
        return true;
    }

    public function createShipment($order, $data){
        if (!$order->canShip()) {
            return ['success' => 0, 'message' => __('Can\'t not create shipment, please try again.')];
        }
        $shipment = $this->_convertOrder->toShipment($order);
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if(!in_array($orderItem->getId(), $data['items'])){
                continue;
            }
            if ($orderItem->getIsVirtual()) {
                continue;
            }
            if (!$orderItem->getQtyToShip()) {
                return ['success' => 0, __('SKU %1 is not available to ship.', $orderItem->getSku())];
            }
            $qty = $orderItem->getQtyToShip();
            $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
            $shipment->addItem($shipmentItem);
        }
        try {
            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);

            // Save created Order Shipment
            if(!$shipment->getId()) {
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();
            }
            
            //create tracking code
            $this->addTrackingNumberForShipment($shipment->getId(), $data['carrier_all'], $data['carrier_title_all'], $data['tracking_number_all']);

            // Send Shipment Email
            $this->_shipmentNotifier->notify($shipment);
            // Commend save shipment multi time
//            $shipment->save();
            return ['success' => 1];
        }catch (\Exception $exception){
            $this->marketplaceLogger->logException('Shipment', $exception, [
                'order_id' => $order ? $order->getId() : null,
                'data' => $data,
            ]);
            return ['success' => 0, 'message' => __('Failed to create shipment, please try again.')];
        }
    }
    public function addTrackingNumberForShipment($shipmentId, $carrier, $title, $number){
        $shipment = $this->shipmentRepository->get($shipmentId);
        //remove old track and add new, only one tracking number
        $trackCollection = $shipment->getTracksCollection();
        foreach ($trackCollection as $_track) {
            $_track->delete();
        }
        $track = $this->trackFactory->create()->setNumber(
            $number
        )->setCarrierCode(
            $carrier
        )->setTitle(
            $title
        );
        $shipment->addTrack($track);
        $this->shipmentRepository->save($shipment);
    }
}