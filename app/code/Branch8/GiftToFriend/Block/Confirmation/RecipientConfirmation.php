<?php
declare(strict_types=1);

namespace Branch8\GiftToFriend\Block\Confirmation;

use Magento\Customer\Helper\Address;
use Magento\Framework\App\ObjectManager;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

class RecipientConfirmation extends \Magento\Directory\Block\Data
{
     /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Branch8\GiftToFriend\Helper\Confirm
     */
    protected $giftHelperConfirm;

    /**
     * @var \Branch8\GiftToFriend\Helper\Data
     */
    protected $giftHelper;
     
    /**
     * @var \Branch8\GiftToFriend\Model\SecurityChecker\Config
     */
    protected $securityConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Branch8\GiftToFriend\Helper\Confirm $giftHelperConfirm
     * @param \Branch8\GiftToFriend\Helper\Data $giftHelper
     * @param \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param Address|null $addressHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Branch8\GiftToFriend\Helper\Confirm $giftHelperConfirm,
        \Branch8\GiftToFriend\Helper\Data $giftHelper,
        \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        Address $addressHelper = null,
        array $data = []
    ) {
        $data['addressHelper'] = $addressHelper ?: ObjectManager::getInstance()->get(Address::class);
        $data['directoryHelper'] = $directoryHelper;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->registry = $registry;
        $this->directoryHelper = $directoryHelper;
        $this->giftHelperConfirm = $giftHelperConfirm;
        $this->giftHelper = $giftHelper;
        $this->securityConfig = $securityConfig;
    }

    /**
     * Retrieve current order
     *
     * @return ParentOrder|null
     */
    public function getOrder()
    {
        if ($this->registry->registry('gift_parent_order')) {
            return $this->registry->registry('gift_parent_order'); 
        }
        return null;
    }

    /**
     * Retrieve current order detail
     *
     * @return ParentOrder|null
     */
    public function getOrderDetail()
    {
        if ($this->registry->registry('gift_parent_order_detail')) {
            return $this->registry->registry('gift_parent_order_detail'); 
        }
        return null;
    }

    /**
     * Returns country id
     *
     * @return string
     */
    public function getCountryId()
    {
        $countryId = $this->getData('country_id');
        if ($countryId === null) {
            $countryId = $this->directoryHelper->getDefaultCountry();
        }
        return $countryId;
    }

    /**
     * Return the id of the region being edited.
     *
     * @return int region id
     */
    public function getRegionId()
    {
        return 0 ;
    }

    public function getCustomerServiceUrl() {
       return $this->getUrl('helpdesk/ticket');
    }

    public function getFAQUrl() {
        $faqCategoryId = $this->giftHelper->getFAQCatConfig();
        if($faqCategoryId && $faqCategoryId !== '') {
            return $this->getUrl('faq',['_query' => ['tabid' => $faqCategoryId]]);
        }
        return $this->getUrl('faq');
    }

    public function getContactServiceUrl($order) {
        return $this->getUrl('gift-order/giftbox/service', ['id' => $order->getId()]);
    }

    public function getConfirmActionUrl() {
        return $this->getUrl('gift-order/index/submitconfirm');
    }

    public function getSendSMSActionUrl() {
        return $this->getUrl('gift-order/index/confirmcode');
    }
    
    public function giftOrderConfirmationSuccessUrl() {
        return $this->getUrl('gift-order/index/confirmsuccess/', ['code' => $this->getGiftCode()]);
    }

    public function getGiftCode()
    {
        return $this->getRequest()->getParam('code');
    }

    public function isVirtual() {
        $order = $this->getOrder();
        return $order ? $order->isVirtual() : false;
    }

    public function isBuyerFilled($type) {
        return $type === \Branch8\GiftToFriend\Model\Config\Source\AddressType::BUYER_INPUT_ADDRESS;
    }

    public function getAddressInfo($order) {
        return $order->getBillingAddress() ?: $order->getShippingAddress();
    }

    public function isExpired($order) {
        if (!$order) {
            return false;
        }
        $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
        $exprTime = $this->giftHelperConfirm->getOrderExpiredDate($order->getGiftExpiredAt(), 'Y-m-d H:i:s');
        return strtotime($currentTime) > strtotime($exprTime);
    }

    public function getMaxSmsRequests() {
       return $this->securityConfig->getMaxNumberSmsRequests();
    }

    public function getLimitationTimePeriod() {
        $timePreiodConfig = $this->securityConfig->getLimitationTimePeriod();

        if($timePreiodConfig < 60){
            $timePreiod = __('%1秒', $timePreiodConfig);
        }else if($timePreiodConfig >= 60 && $timePreiodConfig < 60*60){
            $timePreiod = __('%1分', ceil($timePreiodConfig/60));
        }else{
            $timePreiod = __('%1小時', ceil($timePreiodConfig/(60*60)));
        }
        return $timePreiod;
    }

    public function getSmsLifetime() {
        return $this->giftHelperConfirm->getSmsLifetime();
    }
}
