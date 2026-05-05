<?php

namespace Branch8\GA4\Block;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\Dimension;
use Branch8\GA4\Model\ProductHelper;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\Registry;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\View\Element\Template;

class Order extends \Branch8\GA4\Block\Core
{
    private $dimensionModel;

    private $orderCollectionFactory;

    /**
     * @var Registry
     */
    protected $registry;
    protected $parentOrderManagement;
    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;


    public function __construct(
        Template\Context           $context,
        Config                     $config,
        \Branch8\GA4\Model\Storage $storage,
        ProductHelper              $helper,
        CollectionFactory          $orderCollectionFactory,
        Dimension                  $dimension,
        Registry                       $registry,
        HotaiPointHelper $hotaiPointHelper,
        \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement $parentOrderManagement,
        array                      $data = []
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dimensionModel = $dimension;
        $this->registry = $registry;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->hotaiPointHelper = $hotaiPointHelper;
        parent::__construct($context, $config, $storage, $helper, $data);
    }

    /**
     * Returns the product details for the purchase gtm event
     * @return array
     */
    public function getProducts()
    {
        $parentOrder = $this->getParentOrder();
        return $this->productHelper->getGa4CommerceItemList(
            $parentOrder->getAllItems(),
            'order'
        );
    }

    public function getGa4Total()
    {
        return $this->productHelper->getGa4Total();
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->parentOrderManagement->getTotals($this->getParentOrder());
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        return $this->registry->registry('current_parent_order');
    }

    /**
     * Returns the product id's
     * @return array
     */
    public function getProductIds()
    {
        $order = $this->getOrder();
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $products[] = $this->productHelper->getGtmProductId($product);
        }

        return $products;
    }

    /**
     * Retuns the order total (subtotal or grandtotal)
     * @return float
     */
    public function getOrderTotal()
    {
        return $this->parentOrderManagement->getTotalByKey($this->getParentOrder(), 'subtotal')->getValue();
    }
    public function getIncrementId()
    {
        return $this->getParentOrder()->getData('hotai_reserved_order_id');
    }


    /**
     * Retuns the order total (subtotal or grandtotal)
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->parentOrderManagement->getTotalByKey($this->getParentOrder(), 'tax')->getValue();
    }

    /**
     * @return mixed
     */
    public function getShippingAmount()
    {
        return $this->parentOrderManagement->getTotalByKey($this->getParentOrder(), 'shipping_include_tax')->getValue();
    }

    /**
     * @return mixed
     */
    public function getPoints()
    {
        return $this->parentOrderManagement->getTotalByKey($this->getParentOrder(), 'point_used_total')->getValue();
    }

    /**
     * @return int
     */
    public function getRemainingPoints(){
        return $this->hotaiPointHelper->getHotaiPoint();
    }

    /**
     * @return int
     */
    public function getTotalOrderCount()
    {
        $order = $this->getOrder();
        $customerId = $order->getCustomerId();
        if (!$customerId) {
            return 1;
        }

        $orderCollection = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customerId);
        return $orderCollection->count();
    }

    /**
     * @return double
     */
    public function getTotalLifetimeValue()
    {
        $order = $this->getOrder();
        $customerId = $order->getCustomerId();

        if (!$customerId) {
            return $order->getGrandtotal();
        }

        $orderTotals = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customerId)
            ->addFieldToSelect('*');

        $grandTotals = $orderTotals->getColumnValues('grand_total');
        $refundTotals = $orderTotals->getColumnValues('total_refunded');

        return array_sum($grandTotals) - array_sum($refundTotals);
    }
}
