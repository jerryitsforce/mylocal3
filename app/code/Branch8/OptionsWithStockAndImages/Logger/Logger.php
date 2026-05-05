<?php

namespace Branch8\OptionsWithStockAndImages\Logger;

use Branch8\OptionsWithStockAndImages\Helper\Data;
use Monolog\DateTimeImmutable;
use Monolog\Logger as MonologLogger;

class Logger extends \Webkul\OptionsWithStockAndImages\Logger\Logger
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param string $name
     * @param Data $helper
     * @param array $handlers
     * @param array $processors
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        $name,
        Data $helper,
        array $handlers = [],
        array $processors = [],
        ?\DateTimeZone $timezone = null
    ) {
        $this->helper = $helper;
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
        if ($this->helper->isLogTypeEnabled('variations-fix-ready-to-ship-qty.log')) {
            return parent::addRecord($level, $message, $context, $datetime);
        }

        if ($level >= MonologLogger::ERROR) {
             if (!$this->helper->isLogTypeEnabled('exception.log') && 
                 !$this->helper->isLogTypeEnabled('wkosi.log')) {
                 return false;
             }
        } else {
             // For levels below ERROR, we check if either system.log OR wkosi.log is enabled
             // since this logger writes to wkosi.log by default.
             if (!$this->helper->isLogTypeEnabled('system.log') && 
                 !$this->helper->isLogTypeEnabled('wkosi.log')) {
                 return false;
             }
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }
}
