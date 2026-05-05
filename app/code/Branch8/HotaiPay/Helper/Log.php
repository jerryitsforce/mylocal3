<?php

namespace Branch8\HotaiPay\Helper;

use Branch8\HotaiCore\Helper\Logger as CoreLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Log
{
    private const XML_PATH_LOG_FILE = 'branch8_debug/branch8_hotaipay/log_file';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CoreLogger $coreLogger
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CoreLogger $coreLogger
    ) {
    }

    /**
     * Write log message for a specific HotaiPay class.
     *
     * @param string|array $message
     * @param string $className
     * @return void
     */
    public function writeLog(string|array $message, string $className): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $shortClassName = $this->extractClassShortName($className);
        if (!$this->isClassEnabled($shortClassName)) {
            return;
        }

        $this->coreLogger->writeLog($message, 'HotaiPay/' . $shortClassName);
    }

    /**
     * Check whether HotaiPay logging feature is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getSelectedLogFiles() !== [];
    }

    /**
     * Check whether the target class is allowed for logging.
     *
     * @param string $className
     * @return bool
     */
    public function isClassEnabled(string $className): bool
    {
        return in_array($className, $this->getSelectedLogFiles(), true);
    }

    /**
     * Return selected HotaiPay log files from admin config.
     *
     * @return string[]
     */
    private function getSelectedLogFiles(): array
    {
        $selected = (string) $this->scopeConfig->getValue(self::XML_PATH_LOG_FILE, ScopeInterface::SCOPE_STORE);
        if (trim($selected) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $selected))));
    }

    /**
     * Get class basename from fully-qualified class name.
     *
     * @param string $className
     * @return string
     */
    public function extractClassShortName(string $className): string
    {
        $parts = explode('\\', trim($className, '\\'));
        return (string) end($parts);
    }
}
