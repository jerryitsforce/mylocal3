<?php
namespace Branch8\GiftToFriend\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_GIFT_ORDER_GENERAL_IS_ACTIVE = 'gift_order/general/is_active';
    const XML_PATH_GIFT_ORDER_GENERAL_EXPIRED_PERIOD = 'gift_order/general/expired_period';
    const XML_PATH_GIFT_ORDER_GENERAL_SMS_ORDER_CONFIRM_TEMPLATE = 'gift_order/general/sms_order_confirm_template';
    const XML_PATH_GIFT_ORDER_GENERAL_SMS_GIFT_BOX_TEMPLATE= 'gift_order/general/sms_gift_box_template';
    const XML_PATH_GIFT_ORDER_GENERAL_SMS_PERIOD = 'gift_order/general/sms_period';
    const XML_PATH_GIFT_ORDER_GENERAL_GIFTBOX_SESSION_LIFTTIME = 'gift_order/general/giftbox_session_lifttime';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_FIRSTNAME = 'gift_order/placeholder_address/firstname';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_LASTNAME = 'gift_order/placeholder_address/lastname';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_TELEPHONE = 'gift_order/placeholder_address/telephone';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_REGION_ID = 'gift_order/placeholder_address/region_id';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_CITY = 'gift_order/placeholder_address/city';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_CITY_ID = 'gift_order/placeholder_address/city_id';
    const XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_STREET= 'gift_order/placeholder_address/street';
    const XML_PATH_CUSTOMER_SERVICE_FORM_GIFT_CATEGORY = 'gift_order/customer_service_form/gift_category';
    const XML_PATH_CUSTOMER_SERVICE_FORM_FAQ_CATEGORY = 'gift_order/customer_service_form/faq_category';

    protected $customerSession;

    protected $timezone;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
    }

    public function isFeatureEnable(){
        $moduleEnable = $this->_moduleManager->isEnabled('Branch8_GiftToFriend');
        $configEnable = $this->getConfigData(self::XML_PATH_GIFT_ORDER_GENERAL_IS_ACTIVE);
        return $moduleEnable && $configEnable;
    }

    public function getPlaceholderAddress()
    {
        $address = [
            'firstname' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_FIRSTNAME)?? 'FakeName',
            'lastname' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_LASTNAME)?? 'Hotai',
            'telephone' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_TELEPHONE)?? '0988222334',
            'region_id' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_REGION_ID)?? '1168',
            'city_id' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_CITY_ID)?? '',
            'city' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_CITY)?? '西區',
            'street' => $this->getConfigData(self::XML_PATH_GIFT_ORDER_PLACEHOLDER_ADDRESS_STREET)?? '00Fake Street'
        ];
        return $address;
    }
  
    /**
     * get
     *
     * @param  mixed $name
     * @return void | string
     */
    public function getConfigData($name)
    {
        return $this->scopeConfig->getValue($name, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * get
     *
     * @param  mixed $name
     * @return void | string
     */
    public function getFAQCatConfig()
    {
        return $this->getConfigData(self::XML_PATH_CUSTOMER_SERVICE_FORM_FAQ_CATEGORY)?? '';
    }
}
