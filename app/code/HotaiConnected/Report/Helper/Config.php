<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;

class Config extends AbstractHelper
{
    // Slack settings
    public const XML_PATH_SLACK_REPORT_WEBHOOK_URL = 'hotaiconnected_report/slack/report_webhook_url';
    public const XML_PATH_SLACK_COMMAND_WEBHOOK_URL = 'hotaiconnected_report/slack/command_webhook_url';

    // Pending Employee Report settings
    public const XML_PATH_PENDING_EMPLOYEE_API_URL = 'hotaiconnected_report/pending_employee/api_url';
    public const XML_PATH_PENDING_EMPLOYEE_SEARCH_API_URL = 'hotaiconnected_report/pending_employee/search_api_url';
    public const XML_PATH_PENDING_EMPLOYEE_APP_ID = 'hotaiconnected_report/pending_employee/app_id';
    public const XML_PATH_PENDING_EMPLOYEE_APP_KEY = 'hotaiconnected_report/pending_employee/app_key';
    public const XML_PATH_PENDING_EMPLOYEE_EXPORT_PATH = 'hotaiconnected_report/pending_employee/export_path';

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * Get Slack Webhook URL for Daily Reports
     *
     * @return string
     */
    public function getSlackReportWebhookUrl(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_SLACK_REPORT_WEBHOOK_URL);
    }

    /**
     * Get Slack Webhook URL for CLI Commands
     *
     * @return string
     */
    public function getSlackCommandWebhookUrl(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_SLACK_COMMAND_WEBHOOK_URL);
    }

    /**
     * Get Pending Employee API URL (find-member)
     *
     * @return string
     */
    public function getPendingEmployeeApiUrl(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_PENDING_EMPLOYEE_API_URL);
    }

    /**
     * Get Pending Employee Search API URL (search-member)
     *
     * @return string
     */
    public function getPendingEmployeeSearchApiUrl(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_PENDING_EMPLOYEE_SEARCH_API_URL);
    }

    /**
     * Get Pending Employee APP_ID
     *
     * @return string
     */
    public function getPendingEmployeeAppId(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_PENDING_EMPLOYEE_APP_ID);
    }

    /**
     * Get Pending Employee AppKey (decrypted)
     *
     * @return string
     */
    public function getPendingEmployeeAppKey(): string
    {
        $encryptedValue = $this->scopeConfig->getValue(self::XML_PATH_PENDING_EMPLOYEE_APP_KEY);
        return $encryptedValue ? $this->encryptor->decrypt($encryptedValue) : '';
    }

    /**
     * Get Export Path
     *
     * @return string
     */
    public function getExportPath(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_PENDING_EMPLOYEE_EXPORT_PATH) ?: 'var/export';
    }
}
