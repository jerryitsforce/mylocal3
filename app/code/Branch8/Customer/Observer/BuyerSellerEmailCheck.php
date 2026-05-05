<?php

namespace Branch8\Customer\Observer;

class BuyerSellerEmailCheck implements \Magento\Framework\Event\ObserverInterface{

    public function execute(\Magento\Framework\Event\Observer $observer) {
        $customer = $observer->getCustomer();
        if($customer->getData('platform') != 'seller') {
            if($customer->getOrigData('email') != '') {
                $customer->setData('email', $customer->getOrigData('email'));
            }
        }
    }

}