<?php

declare(strict_types=1);

namespace Branch8\Marketplace\Service;

use Branch8\HotaiCore\Helper\Logger as HotaiLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class MarketplaceLogger
{
    /**
     * Admin configuration path for selecting Marketplace log files (multiselect).
     */
    public const XML_PATH_LOG_FILE = 'branch8_debug/branch8_marketplace/log_file';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param HotaiLogger $hotaiLogger
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly HotaiLogger $hotaiLogger
    ) {
    }

    /**
     * Check if Marketplace logging is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getEnabledFiles() !== [];
    }

    /**
     * Return enabled class short names from admin configuration.
     *
     * @return string[]
     */
    public function getEnabledFiles(): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_PATH_LOG_FILE, ScopeInterface::SCOPE_STORE);
        if ($raw === '') {
            return [];
        }

        $items = array_map('trim', explode(',', $raw));
        $items = array_values(array_filter($items, static fn (string $v): bool => $v !== ''));

        return $items;
    }

    /**
     * Determine whether logs for a given class should be written.
     *
     * @param string $className Short class name (e.g. "OrderStatus", "Api", "CancelOrderAction").
     * @return bool
     */
    public function shouldLog(string $className): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return in_array($className, $this->getEnabledFiles(), true);
    }

    /**
     * Write a Marketplace log entry if enabled for the given class.
     *
     * Log file path: var/log/Marketplace/{className}/Y_m_d.log
     *
     * @param string $className Short class name used for folder name.
     * @param string|array $message Message content.
     * @param string|null $fileName Optional filename without extension; null uses the default date filename.
     * @return void
     */
    public function log(string $className, string|array $message, ?string $fileName = null): void
    {
        if (!$this->shouldLog($className)) {
            return;
        }

        $folderName = 'Marketplace/' . trim($className, '/');
        $this->hotaiLogger->writeLog($message, $folderName, $fileName ?? '');
    }

    /**
     * Log an exception for Marketplace code paths if enabled for the given class.
     *
     * This method always records full exception details (message, code, file, line, trace),
     * but whether it is written depends on admin configuration.
     *
     * @param string $className Short class name used for folder name.
     * @param \Throwable $e The exception or error to log.
     * @param array<string, mixed> $context Additional context data.
     * @return void
     */
    public function logException(string $className, \Throwable $e, array $context = []): void
    {
        $payload = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
        ];

        $this->log($className, $payload);
    }
}

