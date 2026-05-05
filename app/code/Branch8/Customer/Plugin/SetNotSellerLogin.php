<?php

namespace Branch8\Customer\Plugin;

class SetNotSellerLogin
{

    protected $cookieMetadataFactory;

    protected $cookieManager;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ){
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }



    public function afterLoginOrCreateLogin($subject, $result){
        $customCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $customCookieMetadata->setDuration(60*60*24*365);
        $customCookieMetadata->setPath('/');
        $customCookieMetadata->setHttpOnly(true);

        $this->cookieManager->setPublicCookie(
            'seller_login',
            '0',
            $customCookieMetadata
        );
        return $result;
    }

}
