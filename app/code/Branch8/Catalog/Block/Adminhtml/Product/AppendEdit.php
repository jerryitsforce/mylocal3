<?php

namespace Branch8\Catalog\Block\Adminhtml\Product;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\SaleperpartnerFactory;

class AppendEdit extends \Magento\Backend\Block\Template{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var
     */
    protected $marketPlaceHelpers;
    /**
     * @var SaleperpartnerFactory
     */
    protected $saleperPartner;
    /**
     * @var \Branch8\MarketplaceStaging\Helper\Data
     */
    protected $marketPlaceStagingHelper;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param MarketplaceHelper $marketplaceHelper
     * @param SaleperpartnerFactory $saleperPartnerFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Branch8\MarketplaceStaging\Helper\Data $marketPlaceStagingHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        MarketplaceHelper $marketplaceHelper,
        SaleperpartnerFactory $saleperPartnerFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Branch8\MarketplaceStaging\Helper\Data $marketPlaceStagingHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->_coreRegistry = $registry;
        $this->marketPlaceHelper = $marketplaceHelper;
        $this->saleperPartner = $saleperPartnerFactory;
        $this->marketPlaceStagingHelper = $marketPlaceStagingHelper;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->scopeConfig = $scopeConfig;
    }

    public function getProduct(){
        return $this->_coreRegistry->registry('current_product');
    }

    public function getSellerInfo(){
        if($this->getProduct()) {
            $sellerId = $this->marketPlaceHelper->getSellerIdByProductId($this->getProduct()->getId());
            return $this->marketPlaceStagingHelper->getCommisionRates($sellerId);
        }
        return null;
    }

    public function getTicketAttributeSet(){
        return $this->scopeConfig->getValue('virtual_ticket/general/ticket_attribute_set');
    }

    public function getProductTypeId()
    {
        if($this->getProduct()){
            return $this->getProduct()->getTypeId();
        }
        return $this->getRequest()->getParam('type');
    }

    public function isAllowNegativeGrossProfit(){
        return (bool)$this->scopeConfig->getValue(\Branch8\MarketplaceProduct\Helper\Config::XML_PATH_IS_ACTIVE_NEGATIVE_GROSS_PROFIT);
    }
}