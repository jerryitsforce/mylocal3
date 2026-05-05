<?php

namespace Branch8\SingleDeviceLogin\Observer;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\SingleDeviceLogin\Model\ForceLogoutAction;
use Branch8\SingleDeviceLogin\Model\LoginSessionFactory;
use Branch8\SingleDeviceLogin\Model\TokenService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\SingleDeviceLogin\Helper\Data as SingleDeviceHelper;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Observer executed when a customer logs in.
 *
 * This observer ensures that a unique device identifier (UUID) is persisted
 * via a cookie on the client and recorded on the server.  When a customer
 * logs in on a new device, the previous device identifier for that
 * customer will be replaced in the database.  Subsequent requests on
 * other devices will detect the mismatch and force the customer to log out.
 */
class NewLoginToken implements ObserverInterface
{
    const SESSION_LOGIN_TOKEN = 'session_login_token';

    public function __construct(
        protected SingleDeviceHelper             $helper,
        protected MobileDetect                   $mobileDetect,
        protected ForceLogoutAction              $forceLogoutAction,
        protected LoginSessionFactory            $loginSessionFactory,
        protected TokenService                   $tokenService,
        protected CookieManagerInterface         $cookieManager,
        protected CookieMetadataFactory          $cookieMetadataFactory,
        protected ScopeConfigInterface           $scopeConfig,
        protected SessionManager                 $sessionManager,
        protected \Magento\Framework\HTTP\Header $httpHeader
    )
    {
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isSingleDeviceLoginEnabledAndUseSocket()) {
            return;
        }
        $customer = $observer->getCustomer();
        if (!$customer || !$customer->getId()) {
            return;
        }
        $newToken = $this->tokenService->generateNewToken();
        $this->trackLogin($customer, $newToken);
        $this->createAndSetCookie($newToken);
    }

    /**
     * @param $customer
     * @param $newToken
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function trackLogin($customer, $newToken)
    {
        $deviceType = $this->mobileDetect->isHotaiApp() ? 'app' : 'web';
        $loginSession = $this->loginSessionFactory->create()->loadByCustomerIdAndDeviceType($customer->getId(), $deviceType);
        if ($loginSession->getId()) {
            $loginSession->setToken($newToken);
        } else {
            $data = [
                'token' => $newToken,
                'device_type' => $deviceType,
                'customer_id' => $customer->getId(),
            ];
            foreach ($data as $key => $value) {
                $loginSession->setData($key, $value);
            }
        }
        $loginSession->setUserAgent($this->httpHeader->getHttpUserAgent())->save();
    }

    /**
     * @param $token
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function createAndSetCookie($token)
    {
        $isSecure = $this->scopeConfig->isSetFlag(
            'web/secure/use_in_frontend',
            ScopeInterface::SCOPE_STORE
        );
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            /*->setDuration($lifetime)*/
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain())
            ->setSecure($isSecure)
            ->setHttpOnly(false)
            ->setSameSite('Lax');
        $this->cookieManager->setPublicCookie(
            self::SESSION_LOGIN_TOKEN,
            $token,
            $metadata
        );
    }
}
