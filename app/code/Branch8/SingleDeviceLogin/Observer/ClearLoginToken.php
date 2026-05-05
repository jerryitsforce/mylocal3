<?php

namespace Branch8\SingleDeviceLogin\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
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
class ClearLoginToken implements ObserverInterface
{
    const SESSION_LOGIN_TOKEN = 'session_login_token';

    public function __construct(
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory  $cookieMetadataFactory,
        protected ScopeConfigInterface   $scopeConfig,
        protected SessionManager         $sessionManager,
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
        $this->cookieManager->deleteCookie(
            self::SESSION_LOGIN_TOKEN,
            $metadata
        );
    }
}
