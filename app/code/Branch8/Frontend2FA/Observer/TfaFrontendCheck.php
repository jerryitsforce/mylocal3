<?php

namespace Branch8\Frontend2FA\Observer;

use Branch8\Frontend2FA\Model\SecretFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;

class TfaFrontendCheck implements ObserverInterface
{
    const BRANCH8_AUTHENTICATOR_GENERAL_ENABLE = 'branch8_authenticator/general/enable';
    const BRANCH8_AUTHENTICATOR_GENERAL_FORCED_GROUPS = 'branch8_authenticator/general/forced_groups';

    const FRONTEND_2_FA_ACCOUNT_SETUP_ROUTE = 'frontend2fa_account_setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_ROUTE = 'frontend2fa_account_authenticate';

    const FRONTEND_2_FA_ACCOUNT_SETUP_PATH = 'frontend2fa/account/setup';
    const FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH = 'frontend2fa/account/authenticate';

    const ALLOW_ROUTES = [
        'marketplace/account/login',
        'marketplace/account/logout',
        'marketplace/account/becomeseller',
        'marketplace/account/register'
    ];

    /**
     * @var ScopeConfigInterface
     */
    public $config;
    /**
     * @var UrlInterface
     */
    public $url;
    /**
     * @var RedirectInterface
     */
    public $redirect;
    /**
     * @var SecretFactory
     */
    public $secretFactory;
    /**
     * @var Session
     */
    public $customerSession;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    protected $seller2FaConfig;

    protected $b8CustomerHelper;

    /**
     * TfaFrontendCheck constructor.
     *
     * @param ScopeConfigInterface                        $config
     * @param Http                                        $redirect
     * @param SecretFactory                               $secretFactory
     * @param Session                                     $customerSession
     * @param UrlInterface                                $url
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        ScopeConfigInterface $config,
        Http $redirect,
        SecretFactory $secretFactory,
        Session $customerSession,
        UrlInterface $url,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Branch8\Marketplace\Model\Config\Data $seller2FaConfig,
        \Branch8\Customer\Helper\Data $b8CustomerHelper
    ) {
        $this->config = $config;
        $this->url = $url;
        $this->redirect = $redirect;
        $this->secretFactory = $secretFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->seller2FaConfig = $seller2FaConfig;
        $this->b8CustomerHelper  = $b8CustomerHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->getValue(self::BRANCH8_AUTHENTICATOR_GENERAL_ENABLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return $this;
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerSession->getCustomer();
        if (!$customer->getId() || !$this->customerSession->isLoggedIn()) {
            return $this;
        }

        if($this->b8CustomerHelper->isLoggedInAndIsBuyer()){
            return $this;
        }

        if ($this->customerSession->get2faSuccessful()) {
            return $this;
        }
        
        $request = $observer->getEvent()->getRequest();
        $fullAction = $request->getFullActionName();

        if (in_array($fullAction, $this->getAllowedRoutes($customer))) {
            return $this;
        }
        
        
        if ($this->isCanPassUrl($fullAction)) {
            return $this;
        }
        
        if ($this->is2faConfiguredForCustomer($customer)) {
            // Redirect to 2FA authentication page
            $redirectionUrl = $this->url->getUrl(self::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_PATH);
            $this->redirect->setRedirect($redirectionUrl);
        } else {
            // Redirect to 2FA setup page
            $this->messageManager->addNoticeMessage(__('You need to set up Two Factor Authentication before continuing.'));
            $redirectionUrl = $this->url->getUrl(self::FRONTEND_2_FA_ACCOUNT_SETUP_PATH);
            $this->redirect->setRedirect($redirectionUrl);
        }

        return $this;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    public function is2faConfiguredForCustomer(\Magento\Customer\Model\Customer $customer)
    {
        $secret = $this->secretFactory->create()->load($customer->getId(), 'customer_id');
        if ($secret->getId() && $secret->getSecret()) {
            return true;
        }

        return false;
    }

    public function getAllowedRoutes(\Magento\Customer\Model\Customer $customer)
    {
        // When 2FA is configured, the customer needs to authenticate
        if ($this->is2faConfiguredForCustomer($customer)) {
            $routes = [self::FRONTEND_2_FA_ACCOUNT_AUTHENTICATE_ROUTE];
        } else {
            $routes = [self::FRONTEND_2_FA_ACCOUNT_SETUP_ROUTE];
        }
        $routes = array_merge($routes, self::ALLOW_ROUTES);

        return $routes;
    }

    protected function getSellerUrlNeedToProtect(){
        $required2FaActions = $this->seller2FaConfig->get('seller_2fa_urls');
        return $required2FaActions;
    }

    protected function isCanPassUrl($fullAction){
        $urlsNeedToProtect = $this->getSellerUrlNeedToProtect();
        if(strpos($fullAction, 'marketplace') === 0){
            /**
             * Buyer URL
             */
            $marketplaceBuyerUrl = [
                'marketplace_seller_profile',/** Seller product list */
                'marketplace_seller_collection'
            ];
            if(!in_array($fullAction, $marketplaceBuyerUrl)){
                return false;
            }
        }
        if(strpos($fullAction, 'sellersubaccount') === 0){
            return false;
        }
        if(in_array($fullAction, $urlsNeedToProtect)){
            return false;
        }

        return true;
    }
}
