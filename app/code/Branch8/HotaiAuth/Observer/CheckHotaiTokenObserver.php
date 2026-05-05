<?php

namespace Branch8\HotaiAuth\Observer;

use Branch8\Customer\Helper\Data as Branch8CustomerHelper;
use Branch8\HotaiAuth\Helper\HotaiLogin;
use Branch8\HotaiAuth\Logger\Logger;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Carbon\Carbon;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\App\ActionFlag;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Webkul\SellerSubAccount\Helper\Data as SubAccountHelper;
use Amasty\Fpc\Helper\Http as AmastyHttp;


/**
 * DO NOT REMOVE: This observer now is important for Hotai App
 * Class CheckHotaiTokenObserver
 * @package Branch8\HotaiAuth\Observer
 */
class CheckHotaiTokenObserver implements ObserverInterface
{
    public const HOTAI_TOKEN_COOKIE_NAME = 'hotai_token';
    public const MAGENTO_TOKEN_COOKIE_NAME = 'magento_token';
    public const XML_PATH_APP_COOKIE_LIFETIME = 'web/cookie/app_cookie_lifetime';

    public const FILTER_ROUTE = [
        'hotai_auth_redirect_index',
        'customer_account_logout',
        'customer_account_login',
        'hotai_auth_loginwithtoken_index',
        'hotai_auth_logintostaging_index',
        'hotaiconnected_account_loginredirect_index'
    ];

    /**
     * @param Session $customerSession
     * @param SessionManager $sessionManager
     * @param RedirectInterface $redirect
     * @param MessageManager $messageManager
     * @param ActionFlag $actionFlag
     * @param Branch8CustomerHelper $b8CustomerHelper
     * @param SubAccountHelper $subAccountHelper
     * @param Http $request
     * @param CacheInterface $cache
     * @param Logger $logger
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        protected readonly Session                $customerSession,
        protected readonly SessionManager         $sessionManager,
        protected readonly RedirectInterface      $redirect,
        protected readonly MessageManager         $messageManager,
        protected readonly ActionFlag             $actionFlag,
        protected readonly Branch8CustomerHelper  $b8CustomerHelper,
        protected readonly SubAccountHelper       $subAccountHelper,
        protected readonly Http                   $request,
        protected readonly CacheInterface         $cache,
        protected readonly Logger                 $logger,
        protected readonly CookieManagerInterface $cookieManager,
        protected readonly CookieMetadataFactory  $cookieMetadataFactory,
        protected readonly ScopeConfigInterface   $scopeConfig,
        protected readonly MobileDetect           $mobileDetect,
        protected readonly HotaiLogin             $hotaiLogin,
        protected readonly CustomerRepositoryInterface $customerRepository,

    ) {
    }

    public function execute(Observer $observer)
    {return;
        $fullActionName = $this->request->getFullActionName();
        $isCrawlerRequest = (bool)$this->request->getHeaders(AmastyHttp::STATUS_HEADER);

        // 排除特定路由
        if (in_array(strtolower($fullActionName), self::FILTER_ROUTE) || $isCrawlerRequest) {
            return;
        }

        if ($this->customerSession->isLoggedIn() && !$this->b8CustomerHelper->isSeller() && !$this->subAccountHelper->isSubAccount()) {
            $this->logger->info('[CheckHotaiTokenObserver] Start checking Hotai token');

            $hotaiToken = $this->sessionManager->getHotaiToken();

//            if($this->mobileDetect->isHotaiApp() && !$hotaiToken) {
//                $hotaiToken = $this->getHotaiTokenFromCookie();
//
//                if (!$hotaiToken) {
//                    //Hotai App
//                    $hotaiToken = $this->getHotaiTokenFromLatestHotaiTokenAttribute();
//                }
//            }


            if (!$hotaiToken) {
                $controller = $observer->getControllerAction();
                $response = $controller->getResponse();

                // 防止已設置的重定向被覆蓋
                if (!$response->isRedirect()) {
                    $this->logger->info('[CheckHotaiTokenObserver] Hotai token not found, redirecting to logout.');
                    $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
                    $this->redirect->redirect($controller->getResponse(), 'customer/account/logout');
                }
            }
//            else {
//                $this->logger->info('[CheckHotaiTokenObserver] Token valid');
//
//                if ($this->mobileDetect->isHotaiApp()) {
//                    // Set hotai_token cookie for Hotai App
//                    $this->setAppCookie(self::HOTAI_TOKEN_COOKIE_NAME, json_encode($this->sessionManager->getData('hotai_token')));
//                }
//
//            }
        }
//        if (!$this->customerSession->isLoggedIn()) {
//            //delete cookies
//            $this->logger->info('[CheckHotaiTokenObserver] Customer not logged in, deleting cookies.');
//            $this->deleteCookies();
//        }
    }

    public function deleteCookies()
    {
        $metadata = $this->cookieMetadataFactory->createCookieMetadata();
        $metadata->setPath('/');

        if ($this->cookieManager->getCookie(self::HOTAI_TOKEN_COOKIE_NAME)) {
            $this->cookieManager->deleteCookie(self::HOTAI_TOKEN_COOKIE_NAME, $metadata);
        }
    }

    /**
     * @return array
     */
    public function getHotaiTokenFromCookie()
    {
        $hotaiToken = [];
        $this->logger->info('[CheckHotaiTokenObserver] Hotai token not found, try to get from cookie.');

        $hotaiTokenCookie = $this->cookieManager->getCookie(self::HOTAI_TOKEN_COOKIE_NAME);
        if ($hotaiTokenCookie) {
            $this->logger->info('[CheckHotaiTokenObserver] Hotai token found in cookie.');
            $hotaiToken = json_decode($hotaiTokenCookie, true);
            $hotaiToken['expiredAt'] = Carbon::parse($hotaiToken['expiredAt']);
            $this->sessionManager->setHotaiToken($hotaiToken);
            $hotaiToken = $this->sessionManager->getHotaiToken(); //Refresh if needed
        }

        return $hotaiToken;
    }

    /**
     * @return array
     */
    public function getHotaiTokenFromLatestHotaiTokenAttribute()
    {
        $hotaiToken = [];
        $this->logger->info('[CheckHotaiTokenObserver] Hotai token not found, try to get from latest_hotai_token attribute.');
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            $hotaiProfile = $this->sessionManager->getHotaiProfile();
            $memberSeq = $hotaiProfile['memberSeq'] ?? null;
            $customer = $this->hotaiLogin->getCustomerByMemberSeq($memberSeq);
            $customerId = $customer->getId() ?? null;
        }

        if (!$customerId) {
            return [];
        }


        try {
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        } catch (\Exception $e) {
            return [];
        }

        if ($customer && $customer->getCustomAttribute('latest_hotai_token')) {
            $hotaiToken = json_decode($customer->getCustomAttribute('latest_hotai_token')->getValue() ?? '', true);
            if (isset($hotaiToken['accessToken'])) {
                $hotaiToken['expiredAt'] = Carbon::parse($hotaiToken['expiredAt']);
                $this->logger->info('[CheckHotaiTokenObserver] Hotai token found in latest_hotai_token attribute. ' . json_encode($hotaiToken));
                $this->sessionManager->setHotaiToken($hotaiToken);
                $hotaiToken = $this->sessionManager->getHotaiToken(); //Refresh if needed
            }
        }

        return $hotaiToken;
    }

    public function setAppCookie($key, $value)
    {
        $cookieLifetime = $this->scopeConfig->getValue(self::XML_PATH_APP_COOKIE_LIFETIME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $customCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $customCookieMetadata->setDuration($cookieLifetime);
        $customCookieMetadata->setPath('/');
        $customCookieMetadata->setHttpOnly(true);

        $this->cookieManager->setPublicCookie(
            $key,
            $value,
            $customCookieMetadata
        );

        return $this;
    }
}
