<?php

namespace Branch8\RestrictedProduct\Logger;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Monolog\DateTimeImmutable;
use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    const XML_PATH_LOG_TYPES = 'branch8_debug/branch8_restrictedproduct/log_types';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param string $name
     * @param ScopeConfigInterface $scopeConfig
     * @param array $handlers
     * @param array $processors
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        $name,
        ScopeConfigInterface $scopeConfig,
        array $handlers = [],
        array $processors = [],
        ?\DateTimeZone $timezone = null
    ) {
        $this->scopeConfig = $scopeConfig;
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
             if (!$this->isLogTypeEnabled('exception.log') && 
                 !$this->isLogTypeEnabled('allow_customer_groups.log')) {
                 return false;
             }
        } else {
             if (!$this->isLogTypeEnabled('system.log') && 
                 !$this->isLogTypeEnabled('allow_customer_groups.log')) {
                 return false;
             }
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }

    /**
     * Support function to check if log type is enabled without using Helper\Data
     *
     * @param string $logType
     * @return bool
     */
    private function isLogTypeEnabled($logType)
    {
        $logTypes = $this->scopeConfig->getValue(
            self::XML_PATH_LOG_TYPES,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($logTypes)) {
            return false;
        }

        $logTypesArray = explode(',', $logTypes);
        return in_array($logType, $logTypesArray);
    }
}
