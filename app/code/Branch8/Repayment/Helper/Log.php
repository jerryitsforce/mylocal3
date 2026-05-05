<?php

namespace Branch8\Repayment\Helper;

use Branch8\HotaiCore\Helper\Logger as CoreLogger;
use Branch8\Repayment\Model\Config\Source\LogOption;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Handle repayment module log writing and option checks.
 */
class Log extends AbstractHelper
{
    /**
     * Base folder for repayment logs.
     */
    private const LOG_BASE_FOLDER = 'Repayment';

    /**
     * Module code used for debug log configuration.
     */
    private const MODULE_CODE = 'Branch8_Repayment';

    /**
     * @var CoreLogger
     */
    private CoreLogger $logger;

    /**
     * Initialize repayment log helper.
     *
     * @param Context $context Magento helper context.
     * @param CoreLogger $logger Core logger helper.
     */
    public function __construct(
        Context $context,
        CoreLogger $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    /**
     * Write a log line when the given log option is enabled.
     *
     * @param string|array $message Log message.
     * @param string $logOptionValue Option value from admin setting.
     */
    public function info(string|array $message, string $logOptionValue): void
    {
        if (!$this->isEnabled($logOptionValue)) {
            return;
        }

        $this->logger->writeLog(
            $message,
            self::LOG_BASE_FOLDER . '/' . $logOptionValue
        );
    }

    /**
     * Write exception details when the given log option is enabled.
     *
     * @param Exception $exception Exception object.
     * @param string $logOptionValue Option value from admin setting.
     * @param string $method Method name where exception occurred.
     * @param array<string, mixed> $context Extra debug context.
     */
    public function exception(Exception $exception, string $logOptionValue, string $method, array $context = []): void
    {
        if (!$this->isEnabled($logOptionValue)) {
            return;
        }

        $message = [
            'type' => 'exception',
            'class' => $logOptionValue,
            'method' => $method,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];

        $this->logger->writeLog(
            $message,
            self::LOG_BASE_FOLDER . '/' . $logOptionValue
        );
    }

    /**
     * Check whether a repayment log option is enabled.
     *
     * @param string $logOptionValue Option value from admin setting.
     * @return bool
     */
    public function isEnabled(string $logOptionValue): bool
    {
        return \Branch8\HotaiCore\Helper\DebugLog::isEnable(self::MODULE_CODE, $logOptionValue);
    }

    /**
     * Return log option value for checkout verify controller.
     *
     * @return string
     */
    public function verifyOption(): string
    {
        return LogOption::COMMAND_VERIFY;
    }

    /**
     * Return log option value for order management model.
     *
     * @return string
     */
    public function orderManagementOption(): string
    {
        return LogOption::MODEL_ORDER_MANAGEMENT;
    }

    /**
     * Return log option value for checkout processor block.
     *
     * @return string
     */
    public function processorBlockOption(): string
    {
        return LogOption::BLOCK_PROCESSOR;
    }
}
