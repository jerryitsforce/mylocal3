<?php

namespace Branch8\EmailNotification\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class ScopeConfig extends AbstractHelper
{
    /**
     * Scope config path
     */
    public const ENABLE   = 'email_notification/email_notification_config/enabled';
    public const EMAIL_TO = 'email_notification/email_notification_config/emails';

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
    public function getScopeConfig(string $authConfig): string
    {
        return (string)$this->_scopeConfig->getValue($authConfig);
    }
}
