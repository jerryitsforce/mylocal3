<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Psr\Log\LoggerInterface;

/**
 * Logger helper for SalesReports
 */
class Logger extends DebugLog
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_SalesReports', 'system')) {
            $this->logger->info((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_SalesReports', 'exception')) {
            $this->logger->critical((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_SalesReports', 'system')) {
            $this->logger->debug((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_SalesReports', 'exception')) {
            $this->logger->error((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_SalesReports', 'system')) {
            $this->logger->warning((string) $message, $context);
        }
    }
}
