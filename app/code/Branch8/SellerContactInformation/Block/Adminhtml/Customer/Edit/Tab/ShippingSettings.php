<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Shipping\Model\Config;

class ShippingSettings extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Branch8\SellerContactInformation\Helper\Data
     */
    protected $helper;

    /**
     * @var \\Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus
     */
    protected $preservationStatusSource;

    public const COMM_TEMPLATE = 'customer/shipping_settings.phtml';

    protected $deliveryModelConfig;

    /**
     * @param \Magento\Framework\Registry                           $registry
     * @param \Magento\Backend\Block\Widget\Context                 $context
     * @param \Branch8\SellerContactInformation\Helper\Data         $helper
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit     $customerEdit
     * @param array                                                 $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        \Branch8\SellerContactInformation\Helper\Data $helper,
        \Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus $preservationStatusSource,
        \Branch8\SellerContactInformation\Helper\SellerShipping $sellerShippingSetting,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit = null,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->helper = $helper;
        $this->customerEdit = $customerEdit ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Webkul\Marketplace\Block\Adminhtml\Customer\Edit::class);
        parent::__construct($context, $data);
        $this->preservationStatusSource = $preservationStatusSource;
        $this->deliveryModelConfig = $sellerShippingSetting;
    }

    /**
     * Set template to itself.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::COMM_TEMPLATE);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getSellerInfo()
    {
        $partner = $this->customerEdit->getSellerInfoCollection();
        return $partner;
    }

    public function getPreservationStatusOptions(){
        return $this->preservationStatusSource->getAllOptions();;
    }

    public function getActiveShippingMethod(){
        $activeCarriers =  $this->deliveryModelConfig->getAllCarriers();
        return $activeCarriers;
    }
}
