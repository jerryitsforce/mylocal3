<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class NotificationConfig
{
    const XML_PATH_ENABLE = 'email_notification/asynchronous/enable';

    const XML_PATH_EMAIL_SENDER = 'email_notification/asynchronous/sender_email_identity';
    const XML_PATH_ADMIN_TEMPLATE = 'email_notification/asynchronous/admin_email_template';
    const XML_PATH_SELLER_TEMPLATE = 'email_notification/asynchronous/seller_email_template';

    const XML_THRESH_HOLD = 'email_notification/asynchronous/thresh_hold';

    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return true
     */
    public function enable()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sellerEmailTemplate()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SELLER_TEMPLATE
        );
    }

    /**
     * @return mixed
     */
    public function adminEmailTemplate()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ADMIN_TEMPLATE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emailSender()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER
        );
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        $threshold = (int)$this->scopeConfig->getValue(
            self::XML_THRESH_HOLD
        );
        return $threshold ?: 100;
    }

    /**
     * @return bool
     */
    public function getOptimize()
    {
        return (bool)$this->scopeConfig->getValue('order_export/optimize/use_writer2');
    }
}
