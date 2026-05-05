<?php

namespace Branch8\AppSession\Observer;

use Branch8\AppSession\Model\TokenManagement;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CustomerLogoutObserver implements ObserverInterface
{
    public function __construct(
        protected TokenManagement $tokenManagement,
        protected CookieManagerInterface $cookieManager,
        protected \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
    }

    public function execute(Observer $observer)
    {
//        $this->tokenManagement->revokeCustomerAccessToken(
//            $observer->getEvent()->getCustomer()->getId()
//        );
        $metadata = $this->cookieMetadataFactory->createCookieMetadata();
        $metadata->setPath('/');
        $this->cookieManager->deleteCookie('hotai_app_tk', $metadata);
    }
}
