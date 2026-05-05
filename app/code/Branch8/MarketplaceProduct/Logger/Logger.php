<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Logger;

use Branch8\MarketplaceProduct\Helper\Data;
use Monolog\DateTimeImmutable;
use Monolog\Logger as MonologLogger;

class Logger extends \Webkul\Marketplace\Logger\Logger
{
    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var string
     */
    protected string $logFile;

    /**
     * @param string $name
     * @param Data $helper
     * @param string $logFile
     * @param array $handlers
     * @param array $processors
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        string $name,
        Data $helper,
        string $logFile = 'branch8-mproduct.log',
        array $handlers = [],
        array $processors = [],
        ?\DateTimeZone $timezone = null
    ) {
        $this->helper = $helper;
        $this->logFile = $logFile;
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @param DateTimeImmutable|null $datetime
     * @return bool
     */
    public function addRecord(int $level, string $message, array $context = [], ?DateTimeImmutable $datetime = null): bool
    {
        // 1. Always allow if the specific log file for this logger is enabled in the Admin multiselect
        if ($this->helper->isLogTypeEnabled($this->logFile)) {
            return parent::addRecord($level, $message, $context, $datetime);
        }

        // 2. Allow fallback to general system/exception logs if they are enabled in THIS module's settings
        if ($level >= MonologLogger::ERROR) {
             if ($this->helper->isLogTypeEnabled('exception.log')) {
                 return parent::addRecord($level, $message, $context, $datetime);
             }
        } elseif ($this->helper->isLogTypeEnabled('system.log')) {
             return parent::addRecord($level, $message, $context, $datetime);
        }

        return false;
    }
}
