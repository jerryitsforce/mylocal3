<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Helper;

use Branch8\HotaiCore\Helper\Logger as HotaiLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Module logger wrapper for Branch8_MarketPlaceParentOrder.
 *
 * This helper routes logs to `var/log/MarketPlaceParentOrder/{{class_name}}/{{Y_m_d}}.log`
 * and respects admin configuration to determine whether the given class is allowed to log.
 */
class Log
{
    public const XML_PATH_LOG_FILE = 'branch8_debug/branch8_marketplaceparentorder/log_file';

    private ScopeConfigInterface $scopeConfig;
    private HotaiLogger $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig Configuration reader.
     * @param HotaiLogger $logger Base logger which writes to var/log.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        HotaiLogger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Write a message log if it's enabled by admin configuration.
     *
     * @param string $className The option value configured in admin (e.g. "OrderManagement").
     * @param string|array $message Message content to log.
     * @param string $scopeType Config scope type.
     * @param int|string|null $scopeCode Config scope code.
     * @return void
     */
    public function log(string $className, string|array $message, string $scopeType = ScopeInterface::SCOPE_STORE, int|string|null $scopeCode = null): void
    {
        if (!$this->isLoggingAllowed($className, $scopeType, $scopeCode)) {
            return;
        }

        $this->logger->writeLog(
            $message,
            $this->buildFolderName($className)
        );
    }

    /**
     * Write exception details if it's enabled by admin configuration.
     *
     * Note: This method should still be called on every exception as requested,
     * but it will respect the admin configuration to decide whether to write the log file.
     *
     * @param string $className The option value configured in admin (e.g. "OrderManagement").
     * @param \Throwable $exception The throwable to log.
     * @param array $context Additional context to include.
     * @param string $scopeType Config scope type.
     * @param int|string|null $scopeCode Config scope code.
     * @return void
     */
    public function logException(
        string $className,
        \Throwable $exception,
        array $context = [],
        string $scopeType = ScopeInterface::SCOPE_STORE,
        int|string|null $scopeCode = null
    ): void {
        if (!$this->isLoggingAllowed($className, $scopeType, $scopeCode)) {
            return;
        }

        $payload = [
            'message' => $exception->getMessage(),
            'type' => get_class($exception),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];

        $this->logger->writeLog(
            $payload,
            $this->buildFolderName($className)
        );
    }

    /**
     * Determine whether the log is allowed for given class name based on configuration.
     *
     * @param string $className The option value configured in admin.
     * @param string $scopeType Config scope type.
     * @param int|string|null $scopeCode Config scope code.
     * @return bool
     */
    public function isLoggingAllowed(string $className, string $scopeType = ScopeInterface::SCOPE_STORE, int|string|null $scopeCode = null): bool
    {
        $raw = $this->scopeConfig->getValue(self::XML_PATH_LOG_FILE, $scopeType, $scopeCode);
        if ($raw === null) {
            return false;
        }

        $rawString = trim((string) $raw);
        if ($rawString === '' || strtolower($rawString) === 'false' || $rawString === '0') {
            return false;
        }

        $selected = array_values(array_filter(array_map('trim', explode(',', $rawString)), static fn ($v) => $v !== ''));
        if ($selected === []) {
            return false;
        }

        return in_array($className, $selected, true);
    }

    /**
     * Build folder name for log path.
     *
     * @param string $className The option value configured in admin.
     * @return string
     */
    private function buildFolderName(string $className): string
    {
        return 'MarketPlaceParentOrder/' . trim($className, '/');
    }
}

