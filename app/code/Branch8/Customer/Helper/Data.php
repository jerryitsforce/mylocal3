<?php
namespace Branch8\Customer\Helper;

use Branch8\HotaiCore\Helper\VirtualProduct;
use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Session\Proxy as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;

class Data extends AbstractHelper {
    const BUYER_EMAIL_PREFIX = 'hotaiauth_';
    const XML_PATH_HOTAIPAY_TOP_CONTENT = 'hotai_auth/hotaipay/hotaipay_top_content';
    const XML_PATH_HOTAIPAY_BOTTOM_CONTENT = 'hotai_auth/hotaipay/hotaipay_bottom_content';
    const XML_PATH_HOTAIPAY_BOTTOM = 'hotai_auth/hotaipay/hotaipay_bottom';
    const XML_PATH_HOTAIPAY_CUSTOMER_BOTTOM = 'hotai_auth/hotaipay_customer/hotaipay_bottom_content';
    const XML_PATH_COOKIE_BLOCK = 'web/cookie/cookie_block';
    const XML_PATH_CUSTOMER_PAGE_CUSTOM_BLOCK = 'hotai_account_page/general/custom_block';
    const XML_PATH_POPUP_PRICE_CHANGE_BLOCK = 'hotai_account_page/popup_content/price_change';
    const XML_PATH_POPUP_POINT_REMINDER_BLOCK = 'hotai_account_page/popup_content/point_reminder';
    const XML_PATH_MEMBERSHIP_LEVEL_DESCRIPTION = 'hotai_account_page/membership/membership_level_description';
    const XML_PATH_MEMBERSHIP_LEVEL_INSTRUCTION_BLOCK = 'hotai_account_page/membership/membership_level_instruction_block';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * @var FilterProvider
     */
    protected $filterProvider;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var SellerCollection
     */
    protected $_sellerCollectionFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    protected HttpContext $httpContext;

    protected $_timeZone;

    protected $_ticketCollectionFactory;

    /** @var VirtualProduct */
    private $hotaiCoreHelper;

    /**
     * @var Random
     */
    private Random $random;

    protected $generalHelperTicket;

    protected $subAccountHelper = null;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;

    protected $cacheSeller = [];

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param BlockFactory $blockFactory
     * @param FilterProvider $filterProvider
     * @param CustomerSession $customerSession
     * @param SellerCollection $sellerCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param HttpContext $httpContext
     * @param TimezoneInterface $timeZone
     * @param VirtualProduct $hotaiCoreHelper
     * @param Random $random
     * @param \Branch8\Customer\Helper\Ticket $generalHelperTicket
     * @param \Magento\Customer\Model\Url $customerUrl
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        BlockFactory $blockFactory,
        FilterProvider $filterProvider,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        SellerCollection $sellerCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        HttpContext $httpContext,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        VirtualProduct $hotaiCoreHelper,
        Random $random,
        \Branch8\Customer\Helper\Ticket $generalHelperTicket,
        \Magento\Customer\Model\Url $customerUrl
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->blockFactory = $blockFactory;
        $this->filterProvider = $filterProvider;
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->httpContext = $httpContext;
        $this->_timeZone = $timeZone;
        $this->hotaiCoreHelper = $hotaiCoreHelper;
        $this->random = $random;
        $this->generalHelperTicket = $generalHelperTicket;
        $this->_customerUrl = $customerUrl;
    }

    public function getPrefixBuyerEmail(){
        return self::BUYER_EMAIL_PREFIX;
    }

    public function getHotaiPayTopContentId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HOTAIPAY_TOP_CONTENT, ScopeInterface::SCOPE_STORE);
    }

    public function getHotaiPayBottomContentId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HOTAIPAY_BOTTOM_CONTENT, ScopeInterface::SCOPE_STORE);
    }

    public function getHotaiPayBottomId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HOTAIPAY_BOTTOM, ScopeInterface::SCOPE_STORE);
    }

    public function getHotaiPayCustomerBottomId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HOTAIPAY_CUSTOMER_BOTTOM, ScopeInterface::SCOPE_STORE);
    }

    public function getCookieBlockId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_COOKIE_BLOCK, ScopeInterface::SCOPE_STORE);
    }

    public function getCustomerPageCustomBlockId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_PAGE_CUSTOM_BLOCK, ScopeInterface::SCOPE_STORE);
    }

    public function getPriceChangePopupBlockId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_POPUP_PRICE_CHANGE_BLOCK, ScopeInterface::SCOPE_STORE);
    }

    public function getPointReminderBlockId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_POPUP_POINT_REMINDER_BLOCK, ScopeInterface::SCOPE_STORE);
    }

    public function getBlockContent($blockId)
    {
        $block = $this->blockFactory->create()->load($blockId);
        if ($block->getIsActive()) {
            return $this->filterProvider->getBlockFilter()->filter($block->getContent());
        }
        return '';
    }

    public function getHotaiPayTopContent()
    {
        $blockId = $this->getHotaiPayTopContentId();
        return $this->getBlockContent($blockId);
    }

    public function getHotaiPayBottomContent()
    {
        $blockId = $this->getHotaiPayBottomContentId();
        return $this->getBlockContent($blockId);
    }

    public function getHotaiPayBottom()
    {
        $blockId = $this->getHotaiPayBottomId();
        return $this->getBlockContent($blockId);
    }

    public function getHotaiPayCustomerBottom()
    {
        $blockId = $this->getHotaiPayCustomerBottomId();
        return $this->getBlockContent($blockId);
    }

    public function getCookieBlock()
    {
        $blockId = $this->getCookieBlockId();
        return $this->getBlockContent($blockId);
    }

    public function getCustomerPageCustomBlock()
    {
        $blockId = $this->getCustomerPageCustomBlockId();
        return $this->getBlockContent($blockId);
    }

    public function getPriceChangePopupContent()
    {
        return $this->getBlockContent($this->getPriceChangePopupBlockId());
    }

    public function getPointReminderContent()
    {
        return $this->getBlockContent($this->getPointReminderBlockId());
    }

    public function getSubAccountHelper()
    {
        if ($this->subAccountHelper === null) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->subAccountHelper = $objectManager->get(\Webkul\SellerSubAccount\Helper\Data::class);
        }
        return $this->subAccountHelper;
    }

    public function isSeller(){
//        $sellerStatus = 0;
        $sellerId = $this->httpContext->getValue('customer_id');
        if($sellerId == null){
            $sellerId = $this->customerSession->getCustomerId();
        }
        if((int)$sellerId == 0){
            return false;
        }
        $model = $this->getSellerCollectionObj($sellerId);
//        foreach ($model as $value) {
//            if ($value->getIsSeller() == 1) {
//                $sellerStatus = $value->getIsSeller();
//            }
//        }
//
//        return $sellerStatus;
        //if there is an record in marketplat_userdata, this is an seller
        if($model->getSize()){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return Bool
     */
    public function isLoggedIn(): bool
    {
        if($this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)){
            return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        }
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return bool
     */
    public function isLoggedInAndIsBuyer(): bool
    {
        if ($this->subAccountHelper === null) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->subAccountHelper = $objectManager->get(\Webkul\SellerSubAccount\Helper\Data::class);
        }
        return (bool) ($this->isLoggedIn() && !$this->isSeller() && !$this->isWaitForSeller() && !$this->subAccountHelper->isSubAccount());
    }

    public function getIsSellerCode(){
        $sellerId = $this->httpContext->getValue('customer_id');
        if($sellerId == null){
            $sellerId = $this->customerSession->getCustomerId();
        }
        if((int)$sellerId == 0){
            return false;
        }
        $model = $this->getSellerCollectionObj($sellerId);
        $seller = $model->getFirstItem();
        if($seller){
            //return seller code, there are many status.
            return $seller->getIsSeller();
        }else{
            return FALSE;
        }
    }

    public function getCustomerId()
    {
        $customerId = $this->httpContext->getValue('customer_id');
        if($customerId == null){
            $customerId = $this->customerSession->getCustomerId();
        }
        return (int)$customerId;
    }

    public function isWaitForSeller(){
        $result = false;
        $sellerId = $this->httpContext->getValue('customer_id');
        if($sellerId == null){
            $sellerId = $this->customerSession->getCustomerId();
        }
        if((int)$sellerId == 0){
            return false;
        }

        $collection = $this->getSellerCollection();
        $collection->addFieldToFilter('seller_id', $sellerId);


        if($collection->getSize()){
            $result = true;
        }

        return $result;
    }

    protected function getSellerCollection(){
        return $this->_sellerCollectionFactory->create();
    }
    public function getCurrentStoreId()
    {
        // give the current store id
        return $this->_storeManager->getStore()->getStoreId();
    }

    public function getSellerCollectionObj($sellerId)
    {
        if(isset($this->cacheSeller[$sellerId])){
            return $this->cacheSeller[$sellerId];
        }
        $collection = $this->getSellerCollection();
        $collection->addFieldToFilter('seller_id', $sellerId);
        $collection->addFieldToFilter('store_id', $this->getCurrentStoreId());
        // If seller data doesn't exist for current store

        if (!$collection->getSize()) {
            $collection = $this->getSellerCollection();
            $collection->addFieldToFilter('seller_id', $sellerId);
            $collection->addFieldToFilter('store_id', 0);
        }

        return $this->cacheSeller[$sellerId] = $collection;
    }


    public function showBod($data){
        if(trim($data) == ''){
            return 'N/A';
        }
        return $this->_timeZone->date($data)->format('Y/m/d');
    }


    public function getMembershipLevelDescription($organization)
    {
        $config = $this->scopeConfig->getValue(self::XML_PATH_MEMBERSHIP_LEVEL_DESCRIPTION, ScopeInterface::SCOPE_STORE);
        $config = $config ? json_decode($config, true) : [];

        //filter by organization key and sort by sort_order desc
        $config = array_filter($config, function($item) use ($organization){
            return (int)$item['organization'] == (int)$organization;
        });

        usort($config, function($a, $b){
            return $b['sort_order'] <=> $a['sort_order'] ;
        });

        return $config;
    }



    public function getMemberLevelInstructionBlockIdentify()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MEMBERSHIP_LEVEL_INSTRUCTION_BLOCK, ScopeInterface::SCOPE_STORE);
    }


    public function getCustomerTicketInProfileHeader()
    {
        if (!$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return 0;
        }

        return $this->generalHelperTicket->getTicketsCollectionCount(Status::STATUS_UNUSED);
    }

    public function getLoginUrl()
    {
        return $this->_customerUrl->getLoginUrl();
    }

    public function getRegisterUrl()
    {
        return $this->_customerUrl->getRegisterUrl();
    }
}

