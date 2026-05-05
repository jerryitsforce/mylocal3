<?php

namespace Branch8\AppSession\Plugin\Session;

use Magento\Framework\Session\Config;

class ModifyCookieLifetime
{
    public const XML_PATH_APP_COOKIE_LIFETIME = 'web/cookie/app_cookie_lifetime';


    public function __construct(
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * Modify the cookie lifetime for the session.
     *
     * @param Config $subject
     * @param int $cookieLifetime
     * @return int
     */
    public function afterGetCookieLifetime(Config $subject, int $cookieLifetime): int
    {
        if ($this->isHotaiApp()) {
//            return (int)$this->scopeConfig->getValue(self::XML_PATH_APP_COOKIE_LIFETIME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            return 34560000;
        }

        return $cookieLifetime;
    }


    private function isHotaiApp() : bool {
        $isHotaiApp = false;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if (str_contains($userAgent, 'HotaiApp')) {
                $isHotaiApp = true;
            }
        }
        return $isHotaiApp;
    }
}
