<?php
declare(strict_types=1);

namespace Branch8\SingleDeviceLogin\Helper;

use Branch8\SingleDeviceLogin\Helper\Logger as CustomLogger;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Name of the cookie used to store the unique device identifier.
     *
     * When a customer logs in we generate a UUID and persist it in this
     * cookie.  Subsequent requests use the cookie value to validate that
     * the current device is still authorised for the logged in customer.
     */
    public const DEVICE_ID_COOKIE_NAME = 'active_device_id';
    const REDIS_SESSION_KEY_PREFIX = 'sdl_customer_session:';
    const SESSION_UNIQUE_ID_KEY = 'session_unique_id';
    const DEFAULT_COOKIE_LIFETIME = 3600; // fallback

    protected $isEnabledLogger = null;
    private CustomLogger $customLogger;

    /**
     * @param Context $context
     * @param CustomLogger $customLogger
     */
    public function __construct(
        Context $context,
        CustomLogger $customLogger
    ) {
        $this->customLogger = $customLogger;
        parent::__construct($context);
    }

    /**
     * Get cookie lifetime from config
     *
     * @return int
     */
    public function getCookieLifetime(): int
    {
        $cookieLifetime = (int) $this->scopeConfig->getValue(
            'web/cookie/cookie_lifetime',
            ScopeInterface::SCOPE_STORE
        );

        return ($cookieLifetime > 0) ? $cookieLifetime : self::DEFAULT_COOKIE_LIFETIME;
    }

    public function isSingleDeviceLoginEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'single_device_login/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isSingleDeviceLoginEnabledAndUseSocket()
    {
        return (bool)$this->scopeConfig->isSetFlag(
            'single_device_login/general/enable_and_use_socket',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isSingleDeviceLoginDebugLoggerEnabled(): bool
    {
        if ($this->isEnabledLogger === null) {
            $this->isEnabledLogger = $this->scopeConfig->isSetFlag(
                'single_device_login/general/enabled_logger',
                ScopeInterface::SCOPE_STORE
            );
        }
        return $this->isEnabledLogger;
    }

    public function getUuidV4()
    {
        $bytes = random_bytes(16);
        // set version to 0100
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        // set bits 6-7 to 10
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    public function logDebug($info)
    {
        if (is_array($info)) {
            $info = json_encode($info);
        }
        if (is_null($info)) {
            $info = 'null';
        }
        $this->customLogger->info($info);
    }
}
