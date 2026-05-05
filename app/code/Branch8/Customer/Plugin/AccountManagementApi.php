<?php

namespace Branch8\Customer\Plugin;

class AccountManagementApi{


    /**
     * @var \Branch8\HotaiAuth\Helper\HotaiLogin
     */
    protected $hotaiLoginHelper;

    protected $customerCollectionFactory;
    /**
     * @param \Branch8\HotaiAuth\Helper\HotaiLogin $hotaiLoginHelper
     */
    public function __construct(
        \Branch8\HotaiAuth\Helper\HotaiLogin $hotaiLoginHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ){
        $this->hotaiLoginHelper = $hotaiLoginHelper;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    public function beforeCreateAccount($subject, $customer, $password, $redirectUrl){
//        $phoneNumberObj = $customer->getCustomAttribute('phone_number');
//        if(!$phoneNumberObj){
//            throw new \Magento\Framework\Exception\LocalizedException(__('phone_number is required.'));
//        }


        $platformObj = $customer->getCustomAttribute('platform');
        if(!$platformObj || trim((string)$platformObj->getValue()) == ''){
            throw new \Magento\Framework\Exception\LocalizedException(__('platform is required.'));
        }

        $platform = $platformObj->getValue();

        if($platform != 'seller'){
            $memberSeqObj = $customer->getCustomAttribute('member_seq');
            if(!$memberSeqObj || trim((string)$memberSeqObj->getValue()) == ''){
                throw new \Magento\Framework\Exception\LocalizedException(__('member_seq is required.'));
            }

            $requestEmail = $customer->getEmail();
            //real buyer email
            $customer->getExtensionAttributes()->setBuyerEmail($requestEmail);
            //fake email to pass validate
            $customer->setEmail($this->hotaiLoginHelper->generateBuyerEmail());
        }

        return [$customer, $password, $redirectUrl];
    }

    protected function validatePhone($phone){
        $collection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('phone_number', $phone);
        if($collection->getSize()){
            return false;
        }
        return true;
    }

}