<?php

namespace Branch8\Customer\Plugin;

class SellerLoginRedirect{
    /**
     * @var \Magento\Customer\Model\Account\Redirect
     */
    protected $redirect;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param \Magento\Customer\Model\Account\Redirect $redirect
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Customer\Model\Account\Redirect $redirect,
        \Magento\Framework\UrlInterface $urlBuilder
    ){
        $this->redirect = $redirect;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * @param $subject
     * @return void
     */
    public function beforeExecute($subject){
        $url = $this->_urlBuilder->getUrl("marketplace/account/dashboard");
        $this->redirect->setRedirectCookie($url);
    }

}