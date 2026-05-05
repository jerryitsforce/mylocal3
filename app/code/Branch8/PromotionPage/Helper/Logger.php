<?php
declare(strict_types=1);

namespace Branch8\PromotionPage\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Psr\Log\LoggerInterface;

/**
 * Logger helper for PromotionPage
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
        if ($this->isEnable('Branch8_PromotionPage', 'promotionPageLoggerDebug')) {
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
        if ($this->isEnable('Branch8_PromotionPage', 'promotionPageLoggerDebug')) {
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
        if ($this->isEnable('Branch8_PromotionPage', 'promotionPageLoggerDebug')) {
            $this->logger->debug((string) $message, $context);
        }
    }
}
