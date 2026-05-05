<?php

namespace Branch8\Customer\Plugin;

class SetLoginUrl
{
    protected $_cookieManager;

    protected $_url;
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\UrlInterface $url
    ){
        $this->_cookieManager = $cookieManager;
        $this->_url = $url;
    }
    public function beforeAuthenticate($subject, $loginUrl = null){
        $isSellerLoggedIn = $this->_cookieManager->getCookie('seller_login');
        if($isSellerLoggedIn){
            $loginUrl = $this->_url->getUrl('marketplace/account/login');
        }
        return [$loginUrl];
    }
}