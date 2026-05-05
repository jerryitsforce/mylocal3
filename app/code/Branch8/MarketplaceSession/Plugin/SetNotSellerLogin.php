<?php

namespace Branch8\MarketplaceSession\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;

class SetNotSellerLogin
{
    protected ScopeConfigInterface $scopeConfig;
    protected                      $cookieMetadataFactory;
    protected                      $cookieManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->scopeConfig           = $scopeConfig;
        $this->cookieManager         = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    public function afterLoginOrCreateLogin($subject, $result)
    {
        // If setting enabled,
        // avoid seller_login set to 0 logic at app/code/Branch8/Customer/Plugin/SetNotSellerLogin.php
        if ($this->isEnabled()) {
            return $result;
        }

        $customCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $customCookieMetadata->setDuration(60 * 60 * 24 * 365);
        $customCookieMetadata->setPath('/');
        $customCookieMetadata->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(
            'seller_login',
            '0',
            $customCookieMetadata
        );

        return $result;
    }

    protected function isEnabled()
    {
        return $this->scopeConfig->getValue(
            \Branch8\MarketplaceSession\Plugin\SessionManager::CONFIG_PATH_SPLIT_SESSION
        ) == 1;
    }
}
