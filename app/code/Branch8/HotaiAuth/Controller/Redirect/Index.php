<?php

namespace Branch8\HotaiAuth\Controller\Redirect;

use AllowDynamicProperties;
use Branch8\AppSession\Model\TokenManagement;
use Branch8\Customer\Helper\Data;
use Branch8\HotaiAuth\Exception\HotaiAuthException;
use Branch8\HotaiAuth\Helper\HotaiLogin;
use Branch8\HotaiAuth\Model\Api\HotaiTokenService;
use Branch8\HotaiAuth\Model\Api\LoginWithToken;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Exception;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManager;
use Psr\Log\LoggerInterface;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

#[AllowDynamicProperties] class Index extends Action
{
    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var Data
     */
    protected $b8CustomerHelper;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;
    private LoggerInterface $logger;

    protected $userProfile = [];
    private SessionManager $sessionManager;
    private MobileDetect $mobileDetect;
    private TokenManagement $tokenManagement;


    /**
     * @param HotaiLogin $hotaiLoginHelper
     * @param Context $context
     * @param HotaiAuthService $hotaiAuthService
     * @param RequestInterface $request
     * @param AccountRedirect $accountRedirect
     * @param Session $customerSession
     * @param Data $b8CustomerHelper
     */
    public function __construct(
        HotaiLogin $hotaiLoginHelper,
        Context $context,
        HotaiAuthService $hotaiAuthService,
        RequestInterface $request,
        AccountRedirect $accountRedirect,
        Session $customerSession,
        Data $b8CustomerHelper,
        HelperData $subAccountHelper,
        LoggerInterface $logger,
        SessionManager $sessionManager,
        MobileDetect $mobileDetect,
        TokenManagement $tokenManagement,
    ) {
        $this->hotaiLoginHelper = $hotaiLoginHelper;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->request          = $request;
        $this->accountRedirect  = $accountRedirect;
        $this->customerSession  = $customerSession;
        parent::__construct($context);
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->subAccountHelper = $subAccountHelper;
        $this->logger = $logger;
        $this->sessionManager   = $sessionManager;
        $this->mobileDetect = $mobileDetect;
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $code       = $this->request->getParam('code');

        if (
            empty($code) ||
            ($this->hotaiLoginHelper->isLoggedIn() && !$this->b8CustomerHelper->isSeller()
                && !$this->b8CustomerHelper->isWaitForSeller()
                && !$this->subAccountHelper->isSubAccount())
        ) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }
        $memberSeq  = $this->request->getParam('memberSeq');
        $codeVerifier = $this->request->getParam('codeVerifier');

        $this->sessionManager->setHotaiMemberSeq($memberSeq);

        if ($codeVerifier) {
            $this->sessionManager->setCodeVerifier($codeVerifier);
        }

        try {
            $this->hotaiAuthService->writeLog('codeVerifier: ' . $codeVerifier);
            $this->hotaiAuthService->writeLog('Initial check and redirect start: ' . $memberSeq);
            $this->hotaiAuthService->getToken($code, $memberSeq);
            $this->hotaiAuthService->writeLog('Initial check and redirect end: ' . $memberSeq);

            $this->hotaiAuthService->writeLog('Get user profile start: ' . $memberSeq);
            $this->userProfile = $this->hotaiAuthService->getUserProfile();
            $this->hotaiAuthService->writeLog('Get user profile end: ' . $memberSeq);

            $this->hotaiAuthService->writeLog('Login or create login start: ' . $memberSeq);
            $this->hotaiLoginHelper->loginOrCreateLogin($this->userProfile);
            $this->hotaiAuthService->writeLog('Login or create login end: ' . $memberSeq);

            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
        } catch (HotaiAuthException $h) {
            $this->setHotaiAppLoginFailedCookie();
            $this->messageManager->addErrorMessage($h->getMessage());
            $this->logger->error('Hotai Login HotaiAuthException error (' . $memberSeq . '):' . print_r($h->getMessage(), true));
            $resultRedirect->setPath('/');
            return $resultRedirect;
        } catch (Exception $e) {
            $this->setHotaiAppLoginFailedCookie();
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error('Hotai Login Exception error (' . $memberSeq . '):' . print_r($e->getMessage(), true));
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        $this->hotaiAuthService->writeLog('Get customer start: ' . $memberSeq);
        $customer = $this->customerSession->getCustomer();
        $this->hotaiAuthService->writeLog('Get customer end: '. $memberSeq);
        if ($this->mobileDetect->isHotaiApp()) {
            $this->setHotaiAppAccessTokenCookie($customer);
        }
        $this->hotaiAuthService->writeLog('Get oneId start: '. $memberSeq);
        $oneId = $customer->getOneId();
        if (empty($oneId)) {
            $this->hotaiAuthService->writeLog('Get oneId api start: ' . $memberSeq);
            $oneId = $this->hotaiAuthService->getHotaiOneId($memberSeq);
            $this->hotaiLoginHelper->setCustomerOneId($customer->getId(), $oneId);
            $customer->setOneId($oneId);
            $this->hotaiAuthService->writeLog('Get oneId api end: ' . $memberSeq);
        }

        $this->hotaiLoginHelper->setHotaiOneId($oneId);
        $this->hotaiAuthService->writeLog('Get oneId end: ' . $memberSeq);


        if ($this->mobileDetect->isHotaiApp()) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        return $this->accountRedirect->getRedirect();
    }

    public function setHotaiAppAccessTokenCookie($customer)
    {
        $this->tokenManagement->revokeCustomerAccessToken($customer->getId());
        $accessToken = $this->tokenManagement->generateCustomerToken($customer);
        $metadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(true)
            ->setSecure($this->getRequest()->isSecure());
        $this->getCookieManager()->setPublicCookie('hotai_app_tk', $accessToken, $metadata);
        $this->getCookieManager()->setPublicCookie('hotai_app_access_token', $accessToken, $metadata);
        $this->triggerSectionReload();
    }
    public function setHotaiAppLoginFailedCookie()
    {
        if ($this->mobileDetect->isHotaiApp()) {
            $metadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata()
                ->setDuration(3600)
                ->setPath('/')
                ->setHttpOnly(true)
                ->setSecure($this->getRequest()->isSecure());
            $this->getCookieManager()->setPublicCookie('hotai_app_login_failed', 1, $metadata);
            $this->triggerSectionReload();
        }
    }

    /**
     * Trigger section reload for webview_bridge
     */
    private function triggerSectionReload()
    {
        $metadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false) // Must be readable by JS
            ->setSecure($this->getRequest()->isSecure());

        $this->getCookieManager()->setPublicCookie(
            'section_data_ids',
            json_encode(['webview_bridge' => time()]),
            $metadata
        );
    }

    public function getUserProfile()
    {
        return $this->userProfile;
    }

    /**
     * Retrieve cookie manager
     *
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     * @deprecated 100.1.0
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     * @deprecated 100.1.0
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }
}
