<?php

namespace Branch8\Marketplace\Helper;

use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class Import extends \Magento\Framework\App\Helper\AbstractHelper{
    const SEPARATOR = ',';
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Webkul\Marketplace\Helper\Orders
     */
    protected $mpOrderHelper;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory
     */
    protected $shipmentItemCollectionFactory;
    /**
     * @var ShipmentTrackInterfaceFactory
     */
    protected $trackFactory;
    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    protected $shipmentHelper;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $orderItemCollectionFactory;

    protected $orderRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Webkul\Marketplace\Helper\Orders $mpOrderHelper
     * @param ShipmentTrackInterfaceFactory $trackFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $shipmentItemCollectionFactory
     * @param Shipment $shipmentHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Webkul\Marketplace\Helper\Orders $mpOrderHelper,
        ShipmentTrackInterfaceFactory $trackFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $shipmentItemCollectionFactory,
        \Branch8\Marketplace\Helper\Shipment $shipmentHelper,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        OrderRepositoryInterface $orderRepository
    ){
        parent::__construct($context);
        $this->mpOrderHelper = $mpOrderHelper;
        $this->orderFactory = $orderFactory;
        $this->trackFactory = $trackFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentItemCollectionFactory = $shipmentItemCollectionFactory;
        $this->shipmentHelper = $shipmentHelper;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $data
     * @return array|array[]
     */
    public function validateImportData($data)
    {
        if(empty($data)) {
            return [['line' => 0, 'error' => 'Invalid file format']];
        }

        // Remove array header row
        array_shift($data);

        $cnt = 0;//head line is 0;
        $error = [];
        foreach($data as $_row){
            $cnt ++;
            $incrementId = $_row[0];
            $sku = $_row[1];
            $trackingNumber = $_row[3];
            $carrierCode = $_row[2];
            $error = [];
            if((string)$trackingNumber == ''){
                $error[] = [
                    'line' => $cnt,
                    'error' => 'Tracking number is empty'
                ];
            }
            if((string)$carrierCode == ''){
                $error[] = [
                    'line' => $cnt,
                    'error' => 'Carrier code is empty'
                ];
            }
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if(!$order->getId()){
                $error[] = [
                    'line' => $cnt,
                    'error' => __("The order #%1 doesn't existed.", $incrementId)->render()
                ];
                return $error;
            }
            $trackingSeller = $this->mpOrderHelper->getOrderinfo($order->getId());
            if (!$trackingSeller) {
                $error[] = [
                    'line' => $cnt,
                    'error' => 'You are not authorize to manage this order.'
                ];
                return $error;
            }else{
                if ($trackingSeller->getOrderId() != $order->getId()) {
                    $error[] = [
                        'line' => $cnt,
                        'error' => 'You are not authorize to manage this order.'
                    ];
                    return $error;
                }
            }

//            $shipmentCount = $order->getShipmentsCollection()->getSize();
//            if($shipmentCount == 0){
//                $error[] = [
//                    'line' => $cnt,
//                    'error' => 'Your order does hot have shipments.'
//                ];
//                return $error;
//            }

            if($sku != ''){
                //check  KSU <=> Order
                $skuArr = explode(self::SEPARATOR, $sku);
                $orderItemCollection = $order->getItemsCollection()
                    ->addAttributeToFilter('sku', array('in' => $skuArr))
                    ->addFieldToFilter('parent_item_id', array('null' => true));
                if($orderItemCollection->getSize() != count($skuArr)){
                    $error[] = [
                        'line' => $cnt,
                        'error' => 'The SKU is not included in the order or it is in a bundle product.'
                    ];
                    return $error;
                }
            }
        }
        return $error;
    }

    /**
     * @param $data
     * @return void
     */
    public function doImport($data)
    {
        // Remove array header row
        array_shift($data);

        foreach($data as $_row){
            $incrementId = $_row[0];
            $orderModel = $this->orderFactory->create()->loadByIncrementId($incrementId);
            $order = $this->orderRepository->get($orderModel->getId());
            $carrier = 'custom';/* because we use custom carrier for all, the new customer carrier at b8sales/shipping/logistics_company */
            $carrierTitle = $_row[2];
            $number = $_row[3];
            
            // Skip this line if tracking number is empty
            if(empty($number) || trim($number) == ''){
                continue;
            }
            
            //import for specify order item
            if(isset($_row[1]) && trim($_row[1]) != ''){
                $this->importSpecifySku($order, $_row[1], $carrier, $carrierTitle, $number);
            }else{
                $this->importForOrder($order, $carrier, $carrierTitle, $number);
            }
        }
    }

    /**
     * @param $order
     * @param $sku
     * @param $carrier
     * @param $title
     * @param $number
     * @return void
     * @throws \Exception
     */
    public function importSpecifySku($order, $skus, $carrier, $title, $number){
        //check for update or create shipment
        $shipmentItem = $this->checkExistedShipment($order, $skus);
        $shipmentId = $shipmentItem->getParentId();
        if($shipmentId){
            //if shipment existed, remove old tracking number, add new tracking number
            $this->shipmentHelper->addTrackingNumberForShipment($shipmentId, $carrier, $title, $number);
        }else{
            //create shipment and add tracking number
            $skuArr = explode(self::SEPARATOR, $skus);
            $orderItemCollection = $this->orderItemCollectionFactory->create()
                ->addAttributeToFilter('order_id', $order->getId())
                ->addFieldToFilter('sku', array('in' => $skuArr))
                ->addFieldToFilter('parent_item_id', array('null' => true))->load();
            $items = [];
            foreach($orderItemCollection as $orderItem){
                $items[] = $orderItem->getId();
            }
            $shipmentData = [
                'items' => $items,
                'carrier_all' => $carrier,
                'carrier_title_all' => $title,
                'tracking_number_all' => $number
            ];
            $this->shipmentHelper->createShipment($order, $shipmentData);
        }

    }

    /**
     * @param $orderItemId
     * @param $carrier
     * @param $title
     * @param $number
     * @return void
     */
    public function importForOrderItemId($orderItemId, $carrier, $title, $number){
        $shipmentItem = $this->shipmentItemCollectionFactory->create()
            ->addFieldToFilter('order_item_id', $orderItemId)
            ->getFirstItem();
        $shipmentId = $shipmentItem->getParentId();
        $shipment = $this->shipmentRepository->get($shipmentId);
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

    public function importForOrderItemIds(){

    }

    public function checkExistedShipment($order, $skus){
        $skuArr = explode(self::SEPARATOR, $skus);
        $orderItemCollection = $this->orderItemCollectionFactory->create()
            ->addAttributeToFilter('order_id', $order->getId())
            ->addFieldToFilter('sku', array('in' => $skuArr))
            ->addFieldToFilter('parent_item_id', array('null' => true))
            ->getFirstItem();
        $orderItemId = $orderItemCollection->getId();

        $shipmentItem = $this->shipmentItemCollectionFactory->create()
            ->addFieldToFilter('order_item_id', $orderItemId)
            ->getFirstItem();

        return $shipmentItem;
    }

//    public function addTrackingNumberForShipment($shipmentId, $carrier, $title, $number){
//        $shipment = $this->shipmentRepository->get($shipmentId);
//        $track = $this->trackFactory->create()->setNumber(
//            $number
//        )->setCarrierCode(
//            $carrier
//        )->setTitle(
//            $title
//        );
//        $shipment->addTrack($track);
//        $this->shipmentRepository->save($shipment);
//    }

    /**
     * @param $order
     * @param $carrier
     * @param $title
     * @param $number
     * @return void
     */
    public function importForOrder($order, $carrier, $title, $number){
        $orderItems = $order->getAllVisibleItems();
        $skusArr = [];
        foreach($orderItems as $orderItem){
            $skusArr[] = $orderItem->getSku();
        }
        $skus = implode(',', $skusArr);
        $this->importSpecifySku($order, $skus, $carrier, $title, $number);
    }
}
