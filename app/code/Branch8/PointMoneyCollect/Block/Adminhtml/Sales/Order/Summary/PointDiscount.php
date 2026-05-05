<?php

namespace Branch8\PointMoneyCollect\Block\Adminhtml\Sales\Order\Summary;

class PointDiscount extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Order
     */
    protected $_order;
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    public function getSource()
    {
        return $this->_source;
    }

    public function displayFullSummary()
    {
        return true;
    }
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();
        $title = 'Point Discount';
        $store = $this->getStore();
        if($this->_order->getPointDiscountTotal() != 0){
            $customAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'point_discount',
                    'strong' => false,
                    'value' => -$this->_order->getPointDiscountTotal(),
                    'label' => __($title),
                ]
            );
            $parent->addTotal($customAmount, 'shipping_incl_tax');
        }
        return $this;
    }
    /**
     * Get order store object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->_order->getStore();
    }
    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }
}