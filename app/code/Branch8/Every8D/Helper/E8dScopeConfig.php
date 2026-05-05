<?php

namespace Branch8\Every8D\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class E8dScopeConfig extends AbstractHelper
{
    /**
     * Scope config path
     */
    public const E8D_CONFIG_SERVICE_URL  = "every8d/general/service_url";
    public const E8D_CONFIG_SMS_USER     = "every8d/general/sms_user";
    public const E8D_CONFIG_SMS_PASSWORD = "every8d/general/sms_password";

    /**
     * @var array
     */
    private array $e8dConfig = [
        self::E8D_CONFIG_SERVICE_URL,
        self::E8D_CONFIG_SMS_USER,
        self::E8D_CONFIG_SMS_PASSWORD,
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
     * @param string $e8dConfig
     * @return string
     */
    public function getE8dScopeConfig(string $e8dConfig): string
    {
        if (!in_array($e8dConfig, $this->e8dConfig, true)) {
            return '';
        }
        return (string)$this->_scopeConfig->getValue($e8dConfig);
    }
}
