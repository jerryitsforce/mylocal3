<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Helper;

use Psr\Log\LoggerInterface;
use Branch8\HotaiCore\Helper\DebugLog;

/**
 * Data helper
 */
class Logger extends DebugLog implements LoggerInterface
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
     * @inheritdoc
     */
    public function emergency($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->emergency((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function alert($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->alert((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function critical($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->critical((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->error((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function warning($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->warning((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function notice($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->notice((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->info((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->debug((string)$message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = []): void
    {
        if ($this->isEnable('Branch8_HelpDesk', 'branch8_helpdesk')) {
            $this->logger->log($level, (string)$message, $context);
        }
    }
}
