<?php

namespace Branch8\SellerDocument\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DOCUMENT_ENABLED = 'seller_info/setup/enabled';
    const SHOW_DOCUMENT_TAB = 'seller_info/setup/show_document_tab';
    const DOCUMENT_TEMPLATE = 'seller_info/setup/document_template';
    const DOCUMENT_EXPIRED_TIME = 'seller_info/setup/document_expired_time';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface 
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Branch8\SellerDocument\Model\ResourceModel\File\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Branch8\SellerDocument\Model\ResourceModel\File\CollectionFactory  $collectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Branch8\SellerDocument\Model\ResourceModel\File\CollectionFactory $collectionFactory
    ){
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getStoreid()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return mixed
     */
    public function enabled()
    {
        return $this->scopeConfig->getValue(self::DOCUMENT_ENABLED, ScopeInterface::SCOPE_STORE, $this->getStoreid());
    }

    /**
     * @return mixed
     */
    public function showDocumentTab()
    {
        return $this->scopeConfig->getValue(self::SHOW_DOCUMENT_TAB, ScopeInterface::SCOPE_STORE, $this->getStoreid());
    }

    /**
     * Get document template file URL
     * @return string
     */
    public function getDocumentTemplateFileUrl()
    {
        $fileName = $this->scopeConfig->getValue(self::DOCUMENT_TEMPLATE, ScopeInterface::SCOPE_STORE, $this->getStoreid());
        if ($fileName == '' || $fileName == null || !is_string($fileName)) {
            return '';
        }

        $mediaUrl = $this->storeManager-> getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $fileUrl = $mediaUrl . '/marketplace/seller_document_template/' . $fileName;
        return $fileUrl;
    }

    /**
     * Get document expired time
     * @return string
     */
    public function getDocumentExpiredTime()
    {
        $expiredTime = $this->scopeConfig->getValue(self::DOCUMENT_EXPIRED_TIME, ScopeInterface::SCOPE_STORE, $this->getStoreid());
        if ($expiredTime == '' || $expiredTime == null || !is_string($expiredTime)) {
            return 90;
        }
        return $expiredTime;
    }

    /**
     * @return array
     */
    public function getSellerDocumentCollection($sellerId = '')
    {
        if (!$sellerId) {
            $sellerId = $this->customerSession->getCustomerId();
        }
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId]);

        return $collection;
    }

    /**
     * @return array
     */
    public function getActiveSellerDocumentCollection($sellerId = '')
    {
        if (!$sellerId) {
            $sellerId = $this->customerSession->getCustomerId();
        }
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId]);
        $collection->addFieldToFilter('status', ['eq' => 1]);

        return $collection;
    }

    /**
     * Get lasted upload document time of seller
     * @return string
     */
    public function getLastedUploadTime()
    {
        $sellerId = $this->customerSession->getCustomerId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId]);
        $collection->setOrder('uploaded_at', 'DESC');
        $collection->setPageSize(1);

        $items = $collection->getItems();
        if (count($items)) {
            $item = current($items);
            $uploadedAt = $item->getData('uploaded_at');
            return $uploadedAt;
        }
        return '';
    }

    /**
     * Fucntion check to show popup for seller upload docuemnts
     * @return boolean
     */
    public function showPopup()
    {
        if (!$this->enabled()) {
            return false;
        }

        //show popup until seller upload document and will reset when expired
        $collection= $this->getActiveSellerDocumentCollection();
        $collection->setOrder('uploaded_at', 'DESC');
        $collection->setPageSize(1);

        $activeDocument = $collection->getFirstItem();
        $lastedUploadTime = $activeDocument->getData('uploaded_at');
        $expiredTime = $activeDocument->getData('expired_time');

        if (!$lastedUploadTime) {
            return true;
        }
        $currentDate = strtotime(date('Y-m-d H:i:s'));
        $userLastActivity = strtotime($lastedUploadTime);
        $difference = $currentDate - $userLastActivity;
        $days = floor($difference / (60 * 60 * 24));
        if ($days >= $expiredTime) {
            return true;// expired
        }
        return false;
    }


}
