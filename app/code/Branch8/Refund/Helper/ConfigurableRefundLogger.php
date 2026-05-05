<?php

declare(strict_types=1);

namespace Branch8\Refund\Helper;

use Branch8\HotaiCore\Helper\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Writes refund debug logs via HotaiCore Logger when the class key is enabled in admin (branch8_debug/branch8_refund/log_file).
 */
class ConfigurableRefundLogger
{
    /**
     * Config path for multiselect of enabled log class keys (comma-separated).
     */
    public const CONFIG_PATH = 'branch8_debug/branch8_refund/log_file';

    /**
     * First path segment under var/log for this module.
     */
    public const FOLDER_PREFIX = 'Refund';

    /**
     * @param ScopeConfigInterface $scopeConfig Store configuration
     * @param Logger $logger HotaiCore file logger (var/log relative paths)
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Logger $logger
    ) {
    }

    /**
     * Whether logging is enabled for the given short class key (must match LogFileOption values).
     *
     * @param string $classKey Stored multiselect value e.g. RefundOperation
     * @return bool True when the key appears in configured log_file
     */
    public function isEnabled(string $classKey): bool
    {
        $classKey = trim($classKey);
        if ($classKey === '') {
            return false;
        }

        $raw = $this->scopeConfig->getValue(self::CONFIG_PATH);
        if ($raw === null || $raw === '') {
            return false;
        }

        $keys = is_array($raw)
            ? $raw
            : array_filter(
                array_map('trim', explode(',', (string) $raw)),
                static fn(string $v): bool => $v !== ''
            );

        return in_array($classKey, $keys, true);
    }

    /**
     * Write a log line when the class key is enabled.
     *
     * @param string $classKey Short class key (folder name under Refund)
     * @param string|array<int|string, mixed> $message Message or structured payload
     * @return void
     */
    public function log(string $classKey, string|array $message): void
    {
        if (!$this->isEnabled($classKey)) {
            return;
        }

        $folder = self::FOLDER_PREFIX . '/' . trim($classKey, '/');
        $this->logger->writeLog($message, $folder);
    }

    /**
     * Log a throwable when the class key is enabled (same gating as {@see log}).
     *
     * @param string $classKey Short class key
     * @param \Throwable $e Exception or error
     * @param string $context Optional prefix context for the message
     * @return void
     */
    public function logException(string $classKey, \Throwable $e, string $context = ''): void
    {
        if (!$this->isEnabled($classKey)) {
            return;
        }

        $lines = [];
        if ($context !== '') {
            $lines[] = $context;
        }
        $lines[] = get_class($e);
        $lines[] = $e->getMessage();
        $lines[] = $e->getFile() . ':' . (string) $e->getLine();

        $trace = $e->getTraceAsString();
        if (strlen($trace) > 4000) {
            $trace = substr($trace, 0, 4000) . '...';
        }
        $lines[] = $trace;

        $this->log($classKey, implode("\n", $lines));
    }
}
