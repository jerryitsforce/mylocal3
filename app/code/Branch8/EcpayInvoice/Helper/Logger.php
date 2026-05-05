<?php
declare(strict_types=1);

namespace Branch8\EcpayInvoice\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Psr\Log\LoggerInterface;

/**
 * Data helper
 */
class Logger extends DebugLog
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Data constructor.
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
    public function error($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_EcpayInvoice','exception')) {
            $this->logger->error((string) $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_EcpayInvoice','exception')) {
            $this->logger->critical((string) $message, $context);
        }
    }
}
