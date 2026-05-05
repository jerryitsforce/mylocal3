<?php
namespace Branch8\GiftToFriend\Controller\GiftBox;

class GiftFormSubmit extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $timezone;

    protected $customerSession;

    protected $scopeConfig;

    protected $giftBoxHelper;
    
    protected $resultJsonFactory;

    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Magento\Customer\Model\Session $customerSession,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
       \Branch8\GiftToFriend\Helper\GiftBox $giftBoxHelper,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->giftBoxHelper = $giftBoxHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();
        /** Check SMS code */
        if(!$request->isPost()){
            return $result->setData(['success' => false, 'message' => __('Invalid request')]);
        }

        $code = $request->getPost('sms_code');
        $smsSentData = $this->customerSession->getGiftBoxConfirmCode();
        if(!$smsSentData){
            return $result->setData(['success' => false, 'message' => __('Invalid request')]);
        }

        $curentTime = $this->timezone->date()->format('Y-m-d H:i:s');
        $smsTime = $smsSentData['created_at'];
        $codeLifetime = $this->scopeConfig->getValue('gift_order/general/sms_period');
        if(strtotime($curentTime) - strtotime($smsTime) > $codeLifetime*60){
            return $result->setData(['success' => false, 'message' => __('The verification code has expired.')]);
        }

        $smsSent = $smsSentData['smsCode'];
        if($smsSent != $code){
            return $result->setData(['success' => false, 'message' => __('The verification code is wrong, please re-enter!')]);
        }
        /** Create gift box session */
        $smsTelephone = $smsSentData['telephone'];
        if(!$this->customerSession->isLoggedIn()){
            $this->giftBoxHelper->setGuestSession($smsTelephone);
        }
        /**
         * Update last access time for all order of recipient's phone
         */
        $this->giftBoxHelper->setLastAccessTime($smsTelephone);
        
        return $result->setData(['success' => true]);
    }
}
