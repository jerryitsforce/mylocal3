<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;

class DestructionDocument extends \Magento\Backend\Block\Template
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
     * @var \Branch8\SellerDocument\Helper\Data
     */
    protected $sellerDocumentHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    protected $blockGrid;

    public const TEMPLATE = 'customer/destruction_document.phtml';

    /**
     * @param \Magento\Framework\Registry                           $registry
     * @param \Magento\Backend\Block\Widget\Context                 $context
     * @param \Branch8\SellerContactInformation\Helper\Data         $helper
     * @param \Branch8\SellerDocument\Helper\Data                   $sellerDocumentHelper
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManager
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit     $customerEdit
     * @param array                                                 $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        \Branch8\SellerContactInformation\Helper\Data $helper,
        \Branch8\SellerDocument\Helper\Data $sellerDocumentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit = null,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->helper = $helper;
        $this->sellerDocumentHelper = $sellerDocumentHelper;
        $this->storeManager = $storeManager;
        $this->customerEdit = $customerEdit ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Webkul\Marketplace\Block\Adminhtml\Customer\Edit::class);
        parent::__construct($context, $data);
    }

    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Grid\DestructionDocuments::class, 'destruction.documents.grid');
        }
        return $this->blockGrid;
    }

    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Get customer id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
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
            $this->setTemplate(static::TEMPLATE);
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

    /**
     * @return array
     */
    public function getActiveSellerDocumentCollection()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->sellerDocumentHelper->getActiveSellerDocumentCollection($sellerId);
        return $collection;
    }

    /**
     * @return array
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    
}
