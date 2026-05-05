<?php

namespace Branch8\CatalogCustom\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
class CustomClass implements ObserverInterface{

    protected $authSession;

    protected $config;

    public function __construct(
        Session $authSession,
        \Magento\Framework\View\Page\Config $config
    ){
        $this->authSession = $authSession;
        $this->config = $config;
    }

    public function execute(Observer $observer){
        if($this->authSession->isLoggedIn()) {
            $userRole = $this->authSession->getUser()->getRole()->getRoleName();
            $userRole = strtolower($userRole);
            $userRole = preg_replace("/[^a-zA-Z0-9]+/", "", $userRole);
            $this->config->addBodyClass($userRole);
        }
    }
}
