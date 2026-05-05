<?php

namespace Branch8\Rma\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Branch8\HotaiCore\Helper\Logger as CoreLogger;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Provide unified log writing for Branch8_Rma module.
 */
class Log extends AbstractHelper
{
    /**
     * Debug setting module key.
     */
    private const MODULE_KEY = 'Branch8_Rma';

    /**
     * Base log folder under var/log.
     */
    private const BASE_FOLDER = 'Rma';

    /**
     * @var CoreLogger
     */
    private CoreLogger $coreLogger;

    /**
     * @param Context $context Helper context.
     * @param CoreLogger $coreLogger Core logger helper.
     */
    public function __construct(
        Context $context,
        CoreLogger $coreLogger
    ) {
        parent::__construct($context);
        $this->coreLogger = $coreLogger;
    }

    /**
     * Write normal message when log option is enabled.
     *
     * @param string|array $message Log message payload.
     * @param string $logOptionValue Log option value.
     * @return void
     */
    public function info(string|array $message, string $logOptionValue): void
    {
        if (!$this->isEnabled($logOptionValue)) {
            return;
        }

        $this->coreLogger->writeLog($message, $this->buildFolder($logOptionValue));
    }

    /**
     * Write exception message when option is enabled.
     *
     * @param Exception $exception Exception object.
     * @param string $logOptionValue Log option value.
     * @param string $method Method name.
     * @param array<string,mixed> $context Extra context.
     * @return void
     */
    public function exception(Exception $exception, string $logOptionValue, string $method, array $context = []): void
    {
        if (!$this->isEnabled($logOptionValue)) {
            return;
        }

        $this->coreLogger->writeLog(
            [
                'type' => 'exception',
                'class' => $logOptionValue,
                'method' => $method,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'context' => $context,
            ],
            $this->buildFolder($logOptionValue)
        );
    }

    /**
     * Check whether a log option is enabled.
     *
     * @param string $logOptionValue Log option value.
     * @return bool
     */
    public function isEnabled(string $logOptionValue): bool
    {
        return DebugLog::isEnable(self::MODULE_KEY, $logOptionValue);
    }

    /**
     * Build physical folder name for current option.
     *
     * @param string $logOptionValue Log option value.
     * @return string
     */
    private function buildFolder(string $logOptionValue): string
    {
        return self::BASE_FOLDER . '/' . $logOptionValue;
    }
}
