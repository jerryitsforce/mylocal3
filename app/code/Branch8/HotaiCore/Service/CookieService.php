<?php

namespace Branch8\HotaiCore\Service;

use AllowDynamicProperties;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;

#[AllowDynamicProperties] class CookieService
{
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        ConfigInterface $sessionConfig,
    ) {
        $this->_cookieManager         = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionConfig          = $sessionConfig;
    }

    /**
     * @param $cookieName
     * @return string|null
     */
    public function getCookie($cookieName): ?string
    {
        return $this->_cookieManager->getCookie($cookieName);
    }

    /**
     * @param $cookieName
     * @return void
     * @throws FailureToSendException
     * @throws InputException
     */
    public function removeCookie($cookieName): void
    {
        $cookieMetadata = $this->_cookieMetadataFactory->createSensitiveCookieMetadata()
            ->setPath($this->sessionConfig->getCookiePath());
        $this->_cookieManager->deleteCookie($cookieName, $cookieMetadata);
    }

    /**
     * @param $cookieName
     * @param $value
     * @param $duration
     * @param bool $httpOnly
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function setCookie($cookieName, $value, $duration, bool $httpOnly = true): void
    {
        $request = ObjectManager::getInstance()->get(Http::class);

        $publicCookieMetadata = $this->_cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath('/')
            ->setSecure($request->isSecure())
            ->setHttpOnly($httpOnly)
            ->setSameSite('Lax');
        $this->_cookieManager->setPublicCookie(
            $cookieName,
            $value,
            $publicCookieMetadata
        );
    }
}
