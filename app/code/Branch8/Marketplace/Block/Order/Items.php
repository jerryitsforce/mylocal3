<?php

namespace Branch8\Marketplace\Block\Order;

use Exception;
use Branch8\Marketplace\Service\MarketplaceLogger;

class Items extends \Magento\Framework\View\Element\Template{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    protected $shipmentCollectionFactory;
    /**
     * @var \Branch8\Sales\Helper\Config
     */
    protected $b8SalesConfig;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings\CollectionFactory
     */
    protected $logisticsSettingsCollectionFactory;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepositoryInterface
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
     * @param \Branch8\Sales\Helper\Config $b8SalesConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings\CollectionFactory $logisticsSettingsCollectionFactory
     * @param MarketplaceLogger $marketplaceLogger
     */
    public  function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepositoryInterface,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Branch8\Sales\Helper\Config $b8SalesConfig,
        \Magento\Customer\Model\Session $customerSession,
        \HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings\CollectionFactory $logisticsSettingsCollectionFactory,
        MarketplaceLogger $marketplaceLogger
    ){
        parent::__construct($context);
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_shipmentRepositoryInterface = $shipmentRepositoryInterface;
        $this->_shippingConfig = $shippingConfig;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->b8SalesConfig = $b8SalesConfig;
        $this->customerSession = $customerSession;
        $this->logisticsSettingsCollectionFactory = $logisticsSettingsCollectionFactory;
        $this->marketplaceLogger = $marketplaceLogger;
    }

    /**
     * Return current order from block data.
     *
     * @return mixed
     */
    public function getOrder(){
        return $this->getData('order');
    }

    /**
     * Build visible items keyed by order item ID.
     *
     * @return array<int, mixed>
     */
    public function getItems(){
        $order = $this->getOrder();
        $orderItems = $order->getAllVisibleItems();
        $items = [];
        foreach($orderItems as $_orderItem){
            $items[$_orderItem->getId()] = $_orderItem;
        }
        return $items;
    }

    /**
     * Load shipments for current order and map them by order item ID.
     *
     * @return array<int, mixed>
     */
    public function getShipments(){
        $order = $this->getOrder();
        $searchCriteria = $this->_searchCriteriaBuilder->addFilter('order_id', $order->getId())->create();
        try {
            $shipments = $this->_shipmentRepositoryInterface->getList($searchCriteria);
            $shipmentData = $shipments->getItems();//all shipments
        } catch (Exception $exception)  {
            $this->marketplaceLogger->logException('Items', $exception, [
                'order_id' => $order ? $order->getId() : null,
            ]);
            $shipmentData = null;
        }
        $shipmentFormated = [];
        foreach ($shipmentData as $key => $shipment) {
            $shipmentItems = $shipment->getItems();
            foreach($shipmentItems as $_items){
                $shipmentFormated[$_items->getOrderItemId()] = $shipment;
            }
        }
        return $shipmentFormated;
    }

    /**
     * Return shipment collection for current order.
     *
     * @return mixed
     */
    public function getShipmentCollection(){
        return $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('order_id', $this->getOrder()->getId());
    }

    /**
     * Extract first tracking data from a shipment.
     *
     * @param mixed $shipment
     * @return array<string, mixed>
     */
    public function getTrackingData($shipment){
        $tracksCollection = $shipment->getTracksCollection();
        $trackData = [];
        foreach ($tracksCollection->getItems() as $track) {
            $trackData = [
                'tracking_code' => $track->getTrackNumber(),
                'carrier_title' => $track->getTitle(),
                'carrier_code' => $track->getCarrierCode()
                ];
            break;
        }
        return $trackData;
    }


    /**
     * Return configured logistics carriers.
     *
     * @return array
     */
    public function getCarriers()
    {
        $carrierConfig = $this->b8SalesConfig->getLogisticsCompanies();
        return $carrierConfig;
    }

    /**
     * Get carriers with connection status
     *
     * @return array
     */
    public function getCarriersWithStatus()
    {
        $carriers = $this->getCarriers();
        $sellerId = $this->customerSession->getCustomerId();

        if (!$sellerId) {
            return $carriers;
        }

        // Query logistics settings for this seller
        $collection = $this->logisticsSettingsCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', $sellerId)
                   ->addFieldToFilter('is_active', 1);

        // Get connected carrier names
        $connectedCarriers = [];
        foreach ($collection as $setting) {
            $connectedCarriers[] = $setting->getLogisticsCompanyName();
        }

        // Build result array with status labels
        $result = [];
        foreach ($carriers as $code => $url) {
            $result[$code] = [
                'url' => $url,
                'label' => in_array($code, $connectedCarriers) ? $code . ' (已串接)' : $code,
                'connected' => in_array($code, $connectedCarriers)
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function _getCarriersInstances()
    {
        return $this->_shippingConfig->getAllCarriers($this->getOrder()->getStoreId());
    }

    /**
     * Get waybill by order item ID
     *
     * @param int $orderItemId
     * @return \HotaiConnected\Logistics\Model\LogisticsWaybill|null
     */
    public function getWaybillByItemId($orderItemId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $waybillCollection = $objectManager->create(
            \HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill\CollectionFactory::class
        )->create();

        $waybillCollection->addFieldToFilter('sales_order_item_id', $orderItemId)
                          ->setOrder('created_at', 'DESC');

        return $waybillCollection->getFirstItem()->getId() ? $waybillCollection->getFirstItem() : null;
    }
}