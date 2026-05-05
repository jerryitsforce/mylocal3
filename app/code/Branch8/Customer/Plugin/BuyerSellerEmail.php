<?php

namespace Branch8\Customer\Plugin;

class BuyerSellerEmail{


    protected $appState;

    public function __construct(
        \Magento\Framework\App\State $appState
    ){
        $this->appState = $appState;
    }

    public function afterGetEmail($subject, $result){
        if(!$subject->getCustomAttribute('platform') || ($subject->getCustomAttribute('platform') && $subject->getCustomAttribute('platform')->getValue() == 'seller')){
            return $result;
        }
        return $subject->getCustomAttribute('buyer_email')->getValue();
    }
}
