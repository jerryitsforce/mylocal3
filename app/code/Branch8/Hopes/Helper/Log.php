<?php
declare(strict_types=1);

namespace Branch8\Hopes\Helper;

use Branch8\HotaiCore\Helper\Logger as HotaiCoreLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;

/**
 * Centralized logging helper for Branch8_Hopes module.
 *
 * All logs should be written through this helper to ensure:
 * - Admin config controls whether a class can write logs.
 * - Log files are stored under var/log/Hopes/{ClassName}/{Y_m_d}.log.
 */
class Log
{
    /**
     * Config path: selected log types (CSV string).
     */
    public const XML_PATH_LOG_TYPE = 'branch8_debug/branch8_hopes/log_type';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param HotaiCoreLogger $logger
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly HotaiCoreLogger $logger
    ) {
    }

    /**
     * Determine whether Branch8_Hopes logging is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getSelectedTypes() !== [];
    }

    /**
     * Get selected log types from admin config.
     *
     * @return string[]
     */
    public function getSelectedTypes(): array
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_LOG_TYPE);

        if ($value === null || $value === false) {
            return [];
        }

        $value = trim((string) $value);
        if ($value === '' || strtolower($value) === 'false') {
            return [];
        }

        $items = array_map('trim', explode(',', $value));
        $items = array_filter($items, static fn(string $v): bool => $v !== '');

        return array_values(array_unique($items));
    }

    /**
     * Check whether the given log type is allowed to write logs.
     *
     * @param string $logType Example: "TCatEodManagement"
     * @return bool
     */
    public function canLog(string $logType): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $selected = $this->getSelectedTypes();
        if (empty($selected)) {
            return false;
        }

        return in_array($logType, $selected, true);
    }

    /**
     * Write a log message for a given log type.
     *
     * @param string $logType Example: "SendRmaToHopes"
     * @param string|array $message
     * @param string $fileName Optional file name without extension
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function log(string $logType, string|array $message, string $fileName = ''): void
    {
        if (!$this->canLog($logType)) {
            return;
        }

        $folder = $this->getFolderNameByLogType($logType);
        $this->logger->writeLog($message, $folder, $fileName);
    }

    /**
     * Write an exception log for a given log type.
     *
     * Note: Whether the exception is written depends on the same admin setting.
     *
     * @param string $logType Example: "Helper_Email"
     * @param \Throwable $exception
     * @param array $context Additional context data to log
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function logException(string $logType, \Throwable $exception, array $context = []): void
    {
        $payload = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];

        $this->log($logType, $payload);
    }

    /**
     * Build folder name based on log type.
     *
     * Example:
     * - "CustomerTicketRepository" => "Hopes/CustomerTicketRepository"
     * - "SendRmaToHopes" => "Hopes/SendRmaToHopes"
     *
     * @param string $logType
     * @return string
     */
    private function getFolderNameByLogType(string $logType): string
    {
        $logType = trim($logType, '/');
        return 'Hopes/' . $logType;
    }
}

