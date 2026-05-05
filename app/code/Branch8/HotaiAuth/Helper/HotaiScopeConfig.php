<?php

namespace Branch8\HotaiAuth\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class HotaiScopeConfig extends AbstractHelper
{
    /**
     * Scope config path
     */
    public const HOTAI_AUTH_CONFIG_PATH_API_DOMAIN       = "hotai_auth/general/api_domain";
    public const HOTAI_AUTH_CONFIG_PATH_API_REDIRECT_URI = "hotai_auth/general/api_redirect_uri";
    public const HOTAI_AUTH_CONFIG_PATH_APP_ID           = "hotai_auth/general/app_id";
    public const HOTAI_AUTH_CONFIG_PATH_CLIENT_ID        = "hotai_auth/general/client_id";
    public const HOTAI_AUTH_CONFIG_PATH_CLIENT_SECRET    = "hotai_auth/general/client_secret";
    public const HOTAI_AUTH_CONFIG_PATH_AES_KEY          = "hotai_auth/general/aes_key";
    public const HOTAI_AUTH_CONFIG_PATH_AES_IV           = "hotai_auth/general/aes_iv";
    public const HOTAI_AUTH_CONFIG_PATH_APP_VERSION      = "hotai_auth/general/app_version";
    public const HOTAI_AUTH_CONFIG_PATH_API_VERSION      = "hotai_auth/general/api_version";
    public const HOTAI_AUTH_CONFIG_PATH_APP_KEY          = "hotai_auth/general/app_key";

    public const MAGENTO_CUSTOMER_REDIRECT_DASHBOARD     = "customer/startup/redirect_dashboard";

    /**
     * Hotai App
     */
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_DOMAIN       = "hotai_auth/hotai_app/api_domain";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_REDIRECT_URI = "hotai_auth/hotai_app/api_redirect_uri";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_ID           = "hotai_auth/hotai_app/app_id";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_CLIENT_ID        = "hotai_auth/hotai_app/client_id";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_CLIENT_SECRET    = "hotai_auth/hotai_app/client_secret";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_AES_KEY          = "hotai_auth/hotai_app/aes_key";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_AES_IV           = "hotai_auth/hotai_app/aes_iv";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_VERSION      = "hotai_auth/hotai_app/app_version";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_VERSION      = "hotai_auth/hotai_app/api_version";
    public const HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_KEY          = "hotai_auth/hotai_app/app_key";

    /**
     * Hotai Go Travel
     */
    public const HOTAI_GO_TRAVEL_CONFIG_PATH_API_DOMAIN = "hotai_auth/hotai_go_travel/api_domain";
    public const HOTAI_GO_TRAVEL_CONFIG_PATH_APP_ID     = "hotai_auth/hotai_go_travel/app_id";
    public const HOTAI_GO_TRAVEL_CONFIG_PATH_AES_KEY    = "hotai_auth/hotai_go_travel/aes_key";
    public const HOTAI_GO_TRAVEL_CONFIG_PATH_AES_IV     = "hotai_auth/hotai_go_travel/aes_iv";

    /**
     * Hotai External Exchange
     */

    public const HOTAI_EXTERNAL_EXCHANGE_CONFIG_IS_PRODUCTION   = "hotai_auth/external_exchange/is_production";
    public const HOTAI_EXTERNAL_EXCHANGE_CONFIG_API_DOMAIN      = "hotai_auth/external_exchange/api_back_domain";
    public const HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK    = "hotai_auth/external_exchange/aes_key";
    public const HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV     = "hotai_auth/external_exchange/aes_iv";
    public const HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_PRODUCTION_AK    = "hotai_auth/external_exchange/production_aes_key";
    public const HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_PRODUCTION_AES_IV    = "hotai_auth/external_exchange/production_aes_iv";

    /**
     * Hotai One ID
     */
    public const HOTAI_ONE_ID_API_DOMAIN = "hotai_auth/one_id/api_domain";
    public const HOTAI_ONE_ID_SERVICE    = "hotai_auth/one_id/service";

    /**
     * @var array
     */
    private array $authConfig = [
        self::HOTAI_AUTH_CONFIG_PATH_API_DOMAIN,
        self::HOTAI_AUTH_CONFIG_PATH_API_REDIRECT_URI,
        self::HOTAI_AUTH_CONFIG_PATH_APP_ID,
        self::HOTAI_AUTH_CONFIG_PATH_CLIENT_ID,
        self::HOTAI_AUTH_CONFIG_PATH_CLIENT_SECRET,
        self::HOTAI_AUTH_CONFIG_PATH_AES_KEY,
        self::HOTAI_AUTH_CONFIG_PATH_AES_IV,
        self::HOTAI_AUTH_CONFIG_PATH_APP_VERSION,
        self::HOTAI_AUTH_CONFIG_PATH_API_VERSION,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_DOMAIN,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_REDIRECT_URI,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_ID,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_CLIENT_ID,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_CLIENT_SECRET,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_AES_KEY,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_AES_IV,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_VERSION,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_VERSION,
        self::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_KEY,
        self::MAGENTO_CUSTOMER_REDIRECT_DASHBOARD,
        self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_IS_PRODUCTION,
        self::HOTAI_GO_TRAVEL_CONFIG_PATH_API_DOMAIN,
        self::HOTAI_GO_TRAVEL_CONFIG_PATH_APP_ID,
        self::HOTAI_GO_TRAVEL_CONFIG_PATH_AES_KEY,
        self::HOTAI_GO_TRAVEL_CONFIG_PATH_AES_IV,
        self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_API_DOMAIN,
        self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK,
        self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV,
        self::HOTAI_AUTH_CONFIG_PATH_APP_KEY,
        self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_PRODUCTION_AK,
        self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_PRODUCTION_AES_IV,
        self::HOTAI_ONE_ID_API_DOMAIN,
        self::HOTAI_ONE_ID_SERVICE,

    ];

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $_scopeConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @param string $authConfig
     * @return string
     */
    public function getAuthScopeConfig(string $authConfig): string
    {
        if (!in_array($authConfig, $this->authConfig, true)) {
            return '';
        }
        return (string)$this->_scopeConfig->getValue($authConfig);
    }

    public function getIsProduction(){
        return $this->_scopeConfig->getValue(self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_IS_PRODUCTION);
    }


    public function getHotaiExternExchangeProductionAESKey() : array
    {
        $config = $this->_scopeConfig->getValue(self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_PRODUCTION_AK);
        if (is_null($config)) {
            return [];
        }

        if ($config && is_string($config)) {
            $config = json_decode($config, true);
        }
        $result = [];
        foreach ($config as $item) {
            $result[$item['platform']] = $item['aes_key'];
        }

        return $result;
    }

    public function getHotaiExternExchangeProductionAESIV() : array
    {
        $config = $this->_scopeConfig->getValue(self::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_PRODUCTION_AES_IV);
        if (is_null($config)) {
            return [];
        }

        if ($config && is_string($config)) {
            $config = json_decode($config, true);
        }
        $result = [];
        foreach ($config as $item) {
            $result[$item['platform']] = $item['aes_iv'];
        }

        return $result;
    }
}
