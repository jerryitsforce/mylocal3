<?php
namespace Branch8\GiftToFriend\Helper;
use Branch8\Rma\Helper\Config\StatusLabel;

class Confirm extends \Magento\Framework\App\Helper\AbstractHelper
{

    const SMS_GIFT_ORDER_CONFIRM_TEMPLATE = 'gift_order/general/sms_order_confirm_template';

    const SMS_GIFT_BOX_TEMPLATE = 'gift_order/general/sms_gift_box_template';

    const SMS_PERIOD = 'gift_order/general/sms_period';

    const SMS_ENVIRONMENT = 'gift_order/general/sms_environment';

    const GIFT_ORDER_EXPIRED_DAYS = 'gift_order/general/expired_period';

    protected $customerSession;

    protected $timezone;

    protected $smsService;

    protected $rmaStatusLabel;

    /**
     * @var \HotaiConnected\FetSms\Api\SmsSenderInterface
     */
    protected $fetSmsSender;

    /**
     * @var \HotaiConnected\FetSms\Helper\Config
     */
    protected $fetSmsConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\Every8D\Services\SmsService $smsService,
        StatusLabel $rmaStatusLabel,
        \HotaiConnected\FetSms\Api\SmsSenderInterface $fetSmsSender,
        \HotaiConnected\FetSms\Helper\Config $fetSmsConfig
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->smsService = $smsService;
        $this->rmaStatusLabel = $rmaStatusLabel;
        $this->fetSmsSender = $fetSmsSender;
        $this->fetSmsConfig = $fetSmsConfig;
    }
    
    public function sendGiftOrderConfirmCode($phoneNumber, $smsCode){
        try{
            if(!(int)$this->scopeConfig->getValue(self::SMS_ENVIRONMENT)){
                return true;
            }
            $smsTemplate = $this->scopeConfig->getValue(self::SMS_GIFT_ORDER_CONFIRM_TEMPLATE);
            $smsSubject = 'Gift order confirmation';
            $smsContent = str_replace('%code', $smsCode, $smsTemplate);

            if ($this->fetSmsConfig->isEnabled()) {
                $this->fetSmsSender->send($phoneNumber, $smsContent, 'Branch8_GiftToFriend_' . __FUNCTION__);
            } else {
                $this->smsService->send($phoneNumber, $smsSubject, $smsContent);
            }
        }catch(\Exception $e){
            return false;
        }

        return true;
        
    }
    public function sendGiftBoxConfirmCode($phoneNumber, $smsCode){
        try{
            if(!(int)$this->scopeConfig->getValue(self::SMS_ENVIRONMENT)){
                return true;
            }
            $smsTemplate = $this->scopeConfig->getValue(self::SMS_GIFT_BOX_TEMPLATE);
            $smsSubject = 'Gift Box Access';
            $smsContent = str_replace('%code', $smsCode, $smsTemplate);

            if ($this->fetSmsConfig->isEnabled()) {
                $this->fetSmsSender->send($phoneNumber, $smsContent, 'Branch8_GiftToFriend_' . __FUNCTION__);
            } else {
                $this->smsService->send($phoneNumber, $smsSubject, $smsContent);
            }
        }catch(\Exception $e){
            return false;
        }
        return true;
        
    }

    public function getGiftOrderConfirmCode(){
        return $this->customerSession->getConfirmCode();
    }

    public function sendSMSNewGiftToRecipient($inputData){
        
    }

    public function getConfirmationUrl($parentOrder){
        if($parentOrder && $parentOrder->getData('is_gift_order') && $parentOrder->getData('gift_code')){
            $url = $this->_urlBuilder->getUrl('gift-order').'?code='.$parentOrder->getData('gift_code');
            return $url;
        }
        return '';
    }
    public function getConfirmationUrlDirectly($code){
        $url = $this->_urlBuilder->getUrl('gift-order').'?code='.$code;
        return $url;
    }

    public function getExpiredDate($createdAt){
        $expiredPeriod = (int)$this->scopeConfig->getValue(self::GIFT_ORDER_EXPIRED_DAYS);
        $expiredDate = $this->timezone->date($createdAt)
            ->add(new \DateInterval('P'.$expiredPeriod.'D'))
            // ->sub(new \DateInterval('PT8H'))
            ->format('Y-m-d 15:59:59');//UTC 0
        return $expiredDate;
    }

    public function getOrderExpiredDate($giftExpiredAt, $format = 'Y/m/d H:i'){
        $expiredDate = $this->timezone->date($giftExpiredAt)
            // ->add(new \DateInterval('PT8H'))
            ->format($format);
        return $expiredDate;
    }

    public function getSmsLifetime(){
        return (int)$this->scopeConfig->getValue(self::SMS_PERIOD);
    }

    public function updateAddress($address, $data){
        $address->setTelephone($data['telephone']);
        $address->setFirstname($data['firstName']);
        $address->setStreet($data['street']);
        $address->setCity($data['city']);
        $address->setRegion($data['region']);
        $address->setRegionId($data['regionId']);

        return $address;
    }

    public function generateRandomString($length = 6) {
        if((int)$this->scopeConfig->getValue(self::SMS_ENVIRONMENT) == 1){
            $characters = '0123456789';
            $randomString = substr(str_shuffle($characters), 0, $length);
        }else{
            $randomString = '123456';
        }
        return $randomString;
    }

    public function isInvalidRmaStatus($rmaStatus){
        $invalidRmaStatus = ['applying_return_reject', 'applying_return_review_reject', 'processing_not_return_but_refund', 
                'applying_return', 'applying_replace'];
        $result = false;
        $statusCode = $this->rmaStatusLabel->getAdminAndSellerPanelRmaStatusTitle($rmaStatus);
        if(!in_array($statusCode, $invalidRmaStatus)){
            return true;
        }

        return $result;
    }
}
