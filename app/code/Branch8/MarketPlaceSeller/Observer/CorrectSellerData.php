<?php

namespace Branch8\MarketPlaceSeller\Observer;

use Magento\Framework\Event\ObserverInterface;

class CorrectSellerData implements ObserverInterface
{
    protected $request;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
    }

    public function execute($observer){
        $request = $observer->getRequest();
        $customer = $observer->getCustomer();
        if(trim((string)$request->getPost('profileurl'))){
            $customer->getExtensionAttributes()->setPlatform('seller');
            $customer->getCustomAttribute('platform')->setValue('seller');
        }
    }

}