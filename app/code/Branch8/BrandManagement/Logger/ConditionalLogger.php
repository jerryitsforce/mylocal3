<?php

namespace Branch8\BrandManagement\Logger;

use Branch8\BrandManagement\Model\Config\DebugLogConfig;
use Branch8\HotaiCore\Helper\Logger as HotaiCoreLogger;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class ConditionalLogger extends AbstractLogger
{
    private const LOG_FOLDER = 'BrandManagement';
    private const DEFAULT_TARGET = 'General';

    /**
     * @param DebugLogConfig $debugLogConfig Feature flag/config gate for BrandManagement logs.
     * @param HotaiCoreLogger $hotaiCoreLogger File logger that writes under var/log/{folder}/.
     * @param string $target Log target bucket name (used as subfolder under BrandManagement/).
     */
    public function __construct(
        private readonly DebugLogConfig $debugLogConfig,
        private readonly HotaiCoreLogger $hotaiCoreLogger,
        private readonly string $target = self::DEFAULT_TARGET
    ) {}

    /**
     * PSR-3 entry point. When disabled, this is a no-op.
     *
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (!$this->debugLogConfig->isTargetEnabled($this->target)) {
            return;
        }

        $payload = [
            'level' => (string) $level,
            'message' => $this->normalizeMessage($message),
        ];

        if (!empty($context)) {
            $payload['context'] = $context;
        }

        $folder = self::LOG_FOLDER . '/' . $this->sanitizeTarget($this->target);
        $this->hotaiCoreLogger->writeLog($payload, $folder);
    }

    /**
     * Convenience wrapper for CRITICAL level.
     *
     * @param mixed $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Convenience wrapper for ERROR level.
     *
     * @param mixed $message
     * @param array $context
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Normalize message into a scalar/array payload suitable for file logging.
     *
     * @param mixed $message
     * @return string|array
     */
    private function normalizeMessage(mixed $message): string|array
    {
        if ($message instanceof \Throwable) {
            return [
                'exception_class' => $message::class,
                'exception_message' => $message->getMessage(),
                'exception_code' => $message->getCode(),
                'exception_file' => $message->getFile(),
                'exception_line' => $message->getLine(),
                'exception_trace' => $message->getTraceAsString(),
            ];
        }

        if (is_array($message)) {
            return $message;
        }

        if (is_object($message) && method_exists($message, '__toString')) {
            return (string) $message;
        }

        return (string) $message;
    }

    /**
     * Sanitize a target name so it can be safely used as a log folder segment.
     *
     * @param string $target
     */
    private function sanitizeTarget(string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return self::DEFAULT_TARGET;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9_\\-]/', '_', $target) ?: self::DEFAULT_TARGET;

        return $sanitized;
    }
}

