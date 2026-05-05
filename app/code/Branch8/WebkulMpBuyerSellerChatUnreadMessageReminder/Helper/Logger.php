<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Psr\Log\LoggerInterface;

/**
 * Logger helper
 */
class Logger extends DebugLog
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Logger constructor.
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
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChatUnreadMessageReminder','system')) {
            $this->logger->info((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_WebkulMpBuyerSellerChatUnreadMessageReminder','exception')) {
            $this->logger->critical((string) $message, $context);
        }
    }
}
