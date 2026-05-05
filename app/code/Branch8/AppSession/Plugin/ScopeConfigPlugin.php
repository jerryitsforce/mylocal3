<?php

namespace Branch8\AppSession\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ScopeConfigPlugin
{
    /**
     * @param ScopeConfigInterface $subject
     * @param string $path
     * @param string $scopeType
     * @param int|string|null $scopeCode
     * @return array
     */
    public function beforeGetValue(ScopeConfigInterface $subject, $path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null): array
    {
        if ($path === 'web/cookie/cookie_lifetime') {
            if ($this->isHotaiApp()) {
                $path = 'web/cookie/app_cookie_lifetime';
            }
        }
        return [$path, $scopeType, $scopeCode];
    }

    public function isHotaiApp() {
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
