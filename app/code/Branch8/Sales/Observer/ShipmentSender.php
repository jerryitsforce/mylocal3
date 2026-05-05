<?php

namespace Branch8\Sales\Observer;

use Branch8\Customer\Model\GetCustomerNickname;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ShipmentSender implements ObserverInterface
{
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagementFactory
     */
    protected $parentOrderManagementFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var \Magento\Framework\Url
     */
    protected $_url;
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    private GetCustomerNickname $customerNickname;
    private ParentOrder $parentOrder;
    private ParentOrderRepositoryInterface $parentOrderRepository;


    public function __construct(
        \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagementFactory $parentOrderManagementFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Url $urlInterface,
        ResourceConnection $resource,
        GetCustomerNickname $customerNickname,
        ParentOrder $parentOrder,
        ParentOrderRepositoryInterface $parentOrderRepository
    )
    {
        $this->parentOrderManagementFactory = $parentOrderManagementFactory;
        $this->_localeDate = $localeDate;
        $this->_url = $urlInterface;
        $this->resource = $resource;
        $this->customerNickname = $customerNickname;
        $this->parentOrder = $parentOrder;
        $this->parentOrderRepository = $parentOrderRepository;
    }

    public function execute(Observer $observer)
    {
       $transportObject = $observer->getData('transportObject');
        $order = $transportObject->getOrder();
       $parentOrderId = $this->parentOrder->getParentOrder(
           $order->getId()
       );
       $subtotalInclTax = $order->getSubtotalInclTax();
       $shippingInclTax = $order->getShippingInclTax();
        if($order->getData('is_flagship_store_process_order')){
            $shippingInclTax = $subtotalInclTax;
            $subtotalInclTax =  0;
        }
       $customData = [
           'order_created_at' => $this->getOrderDate($order->getCreatedAt()),
           'shipment_created_at' => $this->getShipmentDate($transportObject->getShipment()->getCreatedAt()),
           'subtotal_incl_tax' => $order->getOrderCurrency()->formatTxt($subtotalInclTax),
           'shipping_incl_tax' => $order->getOrderCurrency()->formatTxt($shippingInclTax),
           'grand_total' => $order->getOrderCurrency()->formatTxt($order->getGrandTotal()),
           'point_discount_total' => $order->getOrderCurrency()->formatTxt($order->getPointDiscountTotal()),
           'is_point' => $order->getPointDiscountTotal() > 0,
           'url_history' => $this->getOrderUrlDetail(
               $this->getParentOrderIncrementId($order->getId())
           ),
           'customer_nickname' => $this->customerNickname->getCustomerNicknameByCustomerId($order->getCustomerId()),
       ];
       $transportObject->setCustomData($customData);
    }

    public function getParentOrderIncrementId($subOrderId)
    {
        $parentOrderId = $this->parentOrder->getParentOrder($subOrderId);
        if(empty($parentOrderId)) {
            return null;
        }

        $parentOrder = $this->parentOrderRepository->get((int)$parentOrderId);
        return $parentOrder->getDetail()->getIncrementId();
    }

    /**
     * @param $id
     * @return string|null
     */
    public function getOrderUrlDetail($hotaiParentOrderNumber){
        if($hotaiParentOrderNumber){
            return $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
        }
        return $this->_url->getUrl('sales/parentOrder/history');
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function getOrderDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d H:i');
        }catch (\Exception $exception){
            return '';
        }
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function getShipmentDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d');
        }catch (\Exception $exception){
            return '';
        }
    }
}
