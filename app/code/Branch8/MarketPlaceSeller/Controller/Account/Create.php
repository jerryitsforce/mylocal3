<?php

namespace Branch8\MarketPlaceSeller\Controller\Account;

use Magento\Customer\Model\Registration;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class Create extends \Magento\Framework\App\Action\Action{
    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $customerHelper;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     * @param Registration $registration
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        Registration $registration,
        \Branch8\Customer\Helper\Data $customerHelper,
        HelperData $subAccountHelper
    ) {
        $this->session = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->registration = $registration;
        parent::__construct($context);
        $this->customerHelper = $customerHelper;
        $this->subAccountHelper = $subAccountHelper;
    }

    /**
     * Customer register form page
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $customerHelper = $this->customerHelper;
        if (
            $this->session->isLoggedIn()
            && $customerHelper->isSeller()
            && $customerHelper->isWaitForSeller()
            && $this->subAccountHelper->isSubAccount()
        ){
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('markerplace/account/dashboard');
            return $resultRedirect;
        }else if ($this->session->isLoggedIn() && ($customerHelper->isWaitForSeller())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('marketplace/account/becomeseller');
            return $resultRedirect;
        }else if($this->session->isLoggedIn() && !$customerHelper->isSeller() && !$customerHelper->isWaitForSeller() && !$this->subAccountHelper->isSubAccount()){
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }else{
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            return $resultPage;
        }


    }
}