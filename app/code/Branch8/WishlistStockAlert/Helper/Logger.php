<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Logger helper for WishlistStockAlert
 */
class Logger extends DebugLog implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $allowFlag = false;

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
        if ($this->isEnable('Branch8_WishlistStockAlert', 'system') || $this->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
            $this->logger->info((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'exception') || $this->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
            $this->logger->critical((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'system') || $this->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
            $this->logger->debug((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'exception') || $this->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
            $this->logger->error((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'system') || $this->isEnable('Branch8_WishlistStockAlert', 'branch8_wishlist_alert_stock')) {
            $this->logger->warning((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'exception')) {
            $this->logger->emergency((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'exception')) {
            $this->logger->alert((string)$message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WishlistStockAlert', 'system')) {
            $this->logger->notice((string)$message, $context);
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
        if (in_array($level, [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR])) {
            $type = 'exception';
        }

        if ($this->isEnable('Branch8_WishlistStockAlert', $type)) {
            $this->logger->log($level, (string)$message, $context);
        }
    }
}
