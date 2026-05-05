<?php

namespace Branch8\MarketPlaceSeller\Controller\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;

class Logout extends \Magento\Framework\App\Action\Action{

    protected $session;

    public function __construct(
        Context $context,
        Session $customerSession
    ) {
        $this->session = $customerSession;
        parent::__construct($context);
    }

    public function execute(){
        // TODO: Implement execute() method.
        $this->session->logout();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/login');
        return $resultRedirect;
    }
}