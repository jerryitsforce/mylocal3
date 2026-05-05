<?php

namespace Branch8\CustomerTicketTable\Helper;

use Branch8\HotaiCore\Helper\Logger as HotaiCoreLogger;

class Logger
{
    /** @var HotaiCoreLogger */
    private HotaiCoreLogger $logger;

    /** @var LogConfig */
    private LogConfig $logConfig;

    /**
     * @param HotaiCoreLogger $logger
     * @param LogConfig $logConfig
     */
    public function __construct(
        HotaiCoreLogger $logger,
        LogConfig $logConfig
    ) {
        $this->logger = $logger;
        $this->logConfig = $logConfig;
    }

    /**
     * Write a log entry for the given class key.
     *
     * @param string|array $message
     * @param string $classKey
     * @param string $fileName
     * @return void
     */
    public function log(string|array $message, string $classKey, string $fileName = ''): void
    {
        if (!$this->logConfig->isEnabledFor($classKey)) {
            return;
        }

        try {
            $this->logger->writeLog($message, $this->getFolderName($classKey), $fileName);
        } catch (\Throwable $e) {
            // Intentionally swallow logging failures to avoid breaking business logic.
        }
    }

    /**
     * Write an exception entry for the given class key.
     *
     * @param \Throwable $e
     * @param string $classKey
     * @param array $context
     * @return void
     */
    public function logException(\Throwable $e, string $classKey, array $context = []): void
    {
        $payload = [
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        if (!empty($context)) {
            $payload['context'] = $context;
        }

        $this->log($payload, $classKey);
    }

    /**
     * Build folder name under var/log for the given class key.
     *
     * @param string $classKey
     * @return string
     */
    private function getFolderName(string $classKey): string
    {
        return 'CustomerTicketTable/' . trim($classKey, '/');
    }
}

