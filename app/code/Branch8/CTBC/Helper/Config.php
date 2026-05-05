<?php

namespace Branch8\CTBC\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config 
{
    const CTBC_SERVER_URL = "ctbc/server/url";
    const CTBC_SERVER_TIMEOUT = "ctbc/server/timeout";
    const CTBC_SERVER_MACKEY = "ctbc/server/mackey";
    const CTBC_REQUEST_MERID = "ctbc/auth_rev/merid";
    const CTBC_REQUEST_MID = "ctbc/auth_rev/mid";
    const CTBC_REQUEST_TID = "ctbc/auth_rev/tid";
    const CTBC_REQUEST_CURRENCY = 901;

    /** Debug log config paths */
    public const CTBC_DEBUG_LOG_TYPE = 'branch8_debug/b8_ctbc/log_type';
    
    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Magento scope config service.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig= $scopeConfig;
    }

    /**
     * Check whether CTBC debug logging is enabled.
     *
     * @return bool
     */
    public function isDebugLogEnabled(): bool
    {
        return $this->getDebugLogTypes() !== [];
    }

    /**
     * Get enabled log class names from admin multiselect.
     *
     * @return string[]
     */
    public function getDebugLogTypes(): array
    {
        $value = (string) $this->scopeConfig->getValue(self::CTBC_DEBUG_LOG_TYPE);

        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
    
    /**
     * Get CTBC API server URL.
     *
     * @return string|null
     */
    public function getServerUrl()
    {
        return $this->scopeConfig->getValue(self::CTBC_SERVER_URL);
    }
    
    /**
     * Get CTBC API timeout setting.
     *
     * @return string|null
     */
    public function getServerTimeout()
    {
        return $this->scopeConfig->getValue(self::CTBC_SERVER_TIMEOUT);
    }
    
    /**
     * Get CTBC API MAC key.
     *
     * @return string|null
     */
    public function getServerMacKey()
    {
        return $this->scopeConfig->getValue(self::CTBC_SERVER_MACKEY);
    }
    
    /**
     * Get CTBC MERID.
     *
     * @return string|null
     */
    public function getRequestMerid()
    {
        return $this->scopeConfig->getValue(self::CTBC_REQUEST_MERID);
    }

    /**
     * Get CTBC MID.
     *
     * @return string|null
     */
    public function getRequestMid()
    {
        return $this->scopeConfig->getValue(self::CTBC_REQUEST_MID);
    }

    /**
     * Get CTBC TID.
     *
     * @return string|null
     */
    public function getRequestTid()
    {
        return $this->scopeConfig->getValue(self::CTBC_REQUEST_TID);
    }
    
    /**
     * Get CTBC currency code for requests.
     *
     * @return int
     */
    public function getRequestCurrency()
    {
        return self::CTBC_REQUEST_CURRENCY;
    }
}
