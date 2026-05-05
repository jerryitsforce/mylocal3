<?php
namespace Branch8\GiftToFriend\Controller\Index;

class ConfirmCode extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultJsonFactory;

    protected $customerSession;

    protected $parentOrderDetailCollectionFactory;

    protected $giftConfirmHelper;

    protected $timezone;

    protected $securityManager;

    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
       \Magento\Customer\Model\Session $customerSession,
       \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderDetailCollectionFactory,
       \Branch8\GiftToFriend\Helper\Confirm $giftConfirmHelper,
       \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
       \Branch8\GiftToFriend\Model\SmsSecurityManager $securityManager,
       \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->parentOrderDetailCollectionFactory = $parentOrderDetailCollectionFactory;
        $this->giftConfirmHelper = $giftConfirmHelper;
        $this->timezone = $timezone;
        $this->securityManager = $securityManager;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $returnData = [
            'success' => false,
            'message' => ''
        ];
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();
        $orderCode = $request->getPost('order_code');
        try{
            if($this->scopeConfig->getValue(\Branch8\GiftToFriend\Helper\Confirm::SMS_ENVIRONMENT)){
                $this->securityManager->performSecurityCheck(
                    \Branch8\GiftToFriend\Model\SmsSecurityManager::SMS_SECURITY_GIFT_ORDER,
                    $orderCode
                );
            }
        }catch(\Exception $e){
            $returnData['success'] = false;
            $returnData['message_code'] = 'limitReached';
            $returnData['message_title'] = __('Verification Code Send Limit Reached');
            $returnData['message'] = $e->getMessage();
            return $result->setData($returnData);
        }

        
        if(!$request->isPost()){
            $returnData['success'] = false;
            $returnData['message'] = __('Invalid request');
            return $result->setData($returnData);
        }

        $phoneNumber = $request->getPost('phone_number');

        $parentOrder = $this->parentOrderDetailCollectionFactory->create()
            ->addFieldToFilter('gift_code', $orderCode)
            ->getFirstItem();

        if(!$parentOrder->getId() || $parentOrder->getData('gift_code') != $orderCode){
            $returnData['success'] = false;
            $returnData['message'] = __('Invalid order');
            return $result->setData($returnData);
        }

        if($parentOrder->getIsGiftConfirmed()){
            $returnData['success'] = false;
            $returnData['message'] = __('Invalid order status');
            return $result->setData($returnData);
        }

        $orderStatus = $parentOrder->getStatus();
        /** 
         * Pending: normal flow
         * Complete: RMA flow
         */
        if($orderStatus != \Branch8\HotaiCore\Model\Order\Status::STATUS_GIFT_INFO_PENDING
            && $orderStatus != \Branch8\HotaiCore\Model\Order\Status::STATUS_COMPLETE){
            $returnData['success'] = false;
            $returnData['message'] = __('Invalid order status');
            return $result->setData($returnData);
        }
        
        /** Send SMS to Recipient */
        $smsCode = $this->giftConfirmHelper->generateRandomString(6);
        $smsResult = $this->giftConfirmHelper->sendGiftOrderConfirmCode($phoneNumber, $smsCode);
        if($smsResult){
            $smsCreatedAt = $this->timezone->date();
            $confirmCodeData = [
                'smsCode' => $smsCode,
                'created_at' => $smsCreatedAt->format('Y-m-d H:i:s')
            ];
            $this->customerSession->setConfirmCode($confirmCodeData);
            $returnData['success'] = true;
            // $returnData['smsCode'] = $smsCode;
            $smsLifttime = $this->giftConfirmHelper->getSmsLifetime();
            $returnData['lifetime'] = $smsCreatedAt->add(new \DateInterval('PT'.$smsLifttime.'M'))->format('Y-m-d H:i:s');
            return $result->setData($returnData);
        }else{
            $returnData['success'] = false;
            $returnData['message'] = __('Error sending SMS message');
            return $result->setData($returnData);
        }
    }

    
}
