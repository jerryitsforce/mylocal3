<?php

namespace Branch8\MarketPlaceSeller\Controller\Account;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Forgotpassword extends \Magento\Framework\App\Action\Action{

    protected $resultPageFactory;

    protected $session;
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $customerSession;
    }


    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('forgotPassword')->setEmailValue($this->session->getForgottenEmail());

        $this->session->unsForgottenEmail();

        return $resultPage;
    }

}