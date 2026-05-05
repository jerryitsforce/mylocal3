<?php

namespace Branch8\AppSession\Plugin\JwtUserToken;

use Magento\JwtUserToken\Model\Config\ConfigReader;

class ConfigReaderPlugin
{
    private const APP_CUSTOMER_EXPIRATION_CONFIG_PATH = 'webapi/jwtauth/app_customer_expiration';


    public function __construct(
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * @param ConfigReader $subject
     * @param int $result
     * @return int
     */
    public function afterGetCustomerTtl(ConfigReader $subject, int $result): int
    {
        if ($this->isHotaiApp()) {
            return 100 * 365 * 24 * 60; // 100 years
        }
        return $result;
    }

    public function isHotaiApp(): bool
    {
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
