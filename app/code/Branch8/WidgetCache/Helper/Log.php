<?php

namespace Branch8\WidgetCache\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Branch8\HotaiCore\Helper\Logger as CoreLogger;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Provide unified logging for Branch8_WidgetCache module.
 */
class Log extends AbstractHelper
{
    /**
     * Module key used in branch8_debug config.
     */
    private const MODULE_KEY = 'Branch8_WidgetCache';

    /**
     * Base folder for module logs.
     */
    private const BASE_FOLDER = 'WidgetCache';

    /**
     * @var CoreLogger
     */
    private CoreLogger $coreLogger;

    /**
     * @param Context $context Magento helper context.
     * @param CoreLogger $coreLogger Core logger helper.
     */
    public function __construct(Context $context, CoreLogger $coreLogger)
    {
        parent::__construct($context);
        $this->coreLogger = $coreLogger;
    }

    /**
     * Write a normal log line when the option is enabled.
     *
     * @param string|array $message Log message.
     * @param string $optionValue Option value from admin setting.
     * @return void
     */
    public function info(string|array $message, string $optionValue): void
    {
        if (!$this->isEnabled($optionValue)) {
            return;
        }

        $this->coreLogger->writeLog($message, self::BASE_FOLDER . '/' . $optionValue);
    }

    /**
     * Write exception log details when the option is enabled.
     *
     * @param Exception $exception Exception object.
     * @param string $optionValue Option value from admin setting.
     * @param string $method Method name.
     * @param array<string,mixed> $context Extra context data.
     * @return void
     */
    public function exception(Exception $exception, string $optionValue, string $method, array $context = []): void
    {
        if (!$this->isEnabled($optionValue)) {
            return;
        }

        $this->coreLogger->writeLog(
            [
                'type' => 'exception',
                'class' => $optionValue,
                'method' => $method,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'context' => $context,
            ],
            self::BASE_FOLDER . '/' . $optionValue
        );
    }

    /**
     * Check whether the specific option is enabled in admin config.
     *
     * @param string $optionValue Option value from admin setting.
     * @return bool
     */
    public function isEnabled(string $optionValue): bool
    {
        return DebugLog::isEnable(self::MODULE_KEY, $optionValue);
    }
}
