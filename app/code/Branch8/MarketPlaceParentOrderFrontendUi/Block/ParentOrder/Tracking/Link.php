<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\Tracking;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

class Link extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Shipping data
     *
     * @var \Magento\Shipping\Helper\Data
     */
    protected $_shippingData;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Shipping\Helper\Data $shippingData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry                      $registry,
        \Magento\Shipping\Helper\Data                    $shippingData,
        array                                            $data = []
    )
    {
        $this->_shippingData = $shippingData;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Sales\Model\AbstractModel $model
     * @return string
     */
    public function getWindowUrl($model)
    {
        return $this->_shippingData->getTrackingPopupUrlBySalesModel($model);
    }

    /**
     * Retrieve current order model instance
     *
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
}
