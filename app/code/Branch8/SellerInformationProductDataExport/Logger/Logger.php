<?php

namespace Branch8\SellerInformationProductDataExport\Logger;

use Branch8\SellerInformationProductDataExport\Helper\Data;
use Monolog\DateTimeImmutable;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
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
        if ($level >= MonologLogger::ERROR) {
             if (!$this->helper->isLogTypeEnabled('exception.log')) {
                 return false;
             }
        } else {
             if (!$this->helper->isLogTypeEnabled('system.log')) {
                 return false;
             }
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }
}
