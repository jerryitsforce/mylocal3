<?php

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Magento\Contact\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_ENABLE = 'buyer_seller_chat/notification/enable';
    const XML_PATH_QUEUE_REMINDER_DAY_OFFSET = 'buyer_seller_chat/notification/reminder_day_offset';
    const XML_PATH_QUEUE_REMIND_FREQUENCY = 'buyer_seller_chat/notification/remind_frequency';
    const XML_PATH_QUEUE_MAX_RETRY = 'buyer_seller_chat/notification/max_retry';
    const XML_PATH_TIME_INTERVAL = 'buyer_seller_chat/notification/remind_interval';
    const XML_PATH_EMAIL_TEMPLATE = 'buyer_seller_chat/notification/unread_email_template';
    const XML_PATH_EMAIL_SENDER = 'buyer_seller_chat/notification/unread_sender_email_identity';

    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
    )
    {
        $this->scopeConfig = $scopeConfigInterface;
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emailTemplate()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emailSender()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return 6h
     * getReminderFrequency
     * @return int
     */
    public function getReminderFrequency()
    {
        $value = (int)$this->scopeConfig->getValue(
            self::XML_PATH_QUEUE_REMIND_FREQUENCY,
            ScopeInterface::SCOPE_STORE
        );
        return $value ? $value : 6;
    }

    /**
     * @return int
     */
    public function getReminderDayOffset()
    {
        $value = (int)$this->scopeConfig->getValue(
            self::XML_PATH_QUEUE_REMINDER_DAY_OFFSET,
            ScopeInterface::SCOPE_STORE
        );
        return $value ? $value : 7;
    }

    /**
     * @return int
     */
    public function getMaxSendMailNotificationRetry()
    {
        $value = (int)$this->scopeConfig->getValue(
            self::XML_PATH_QUEUE_MAX_RETRY,
            ScopeInterface::SCOPE_STORE
        );
        return $value ? $value : 1;
    }

    /**
     * @return int
     */
    public function getTimeInterval(){
        $value = (int)$this->scopeConfig->getValue(
            self::XML_PATH_TIME_INTERVAL
        );
        return $value ? $value : 15;
    }
}
