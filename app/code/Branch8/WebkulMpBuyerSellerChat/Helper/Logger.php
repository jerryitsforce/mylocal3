<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Psr\Log\LoggerInterface;

/**
 * Logger helper for WebkulMpBuyerSellerChat
 */
class Logger extends DebugLog implements LoggerInterface
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
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'system')) {
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
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'exception')) {
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
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'system')) {
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
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'exception')) {
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
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'system')) {
            $this->logger->warning((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'exception')) {
            $this->logger->emergency((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'exception')) {
            $this->logger->alert((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', 'system')) {
            $this->logger->notice((string) $message, $context);
        }
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $type = 'system';
        if (in_array($level, [\Psr\Log\LogLevel::EMERGENCY, \Psr\Log\LogLevel::ALERT, \Psr\Log\LogLevel::CRITICAL, \Psr\Log\LogLevel::ERROR])) {
            $type = 'exception';
        }

        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChat', $type)) {
            $this->logger->log($level, (string) $message, $context);
        }
    }
}
