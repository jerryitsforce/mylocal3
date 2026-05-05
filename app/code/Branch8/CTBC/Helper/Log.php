<?php

declare(strict_types=1);

namespace Branch8\CTBC\Helper;

use Branch8\HotaiCore\Helper\Logger as HotaiCoreLogger;

class Log
{
    /** @var Config */
    private Config $config;

    /** @var HotaiCoreLogger */
    private HotaiCoreLogger $logger;

    /**
     * @param Config $config CTBC configuration helper.
     * @param HotaiCoreLogger $logger Shared application logger helper.
     */
    public function __construct(
        Config $config,
        HotaiCoreLogger $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Write CTBC log based on admin configuration.
     *
     * @param string|array $message Log message payload.
     * @param string $callerClass Short class name (e.g. Api, OrderStatus).
     * @return void
     */
    public function write(string|array $message, string $callerClass): void
    {
        if (!$this->config->isDebugLogEnabled()) {
            return;
        }

        $enabledTypes = $this->config->getDebugLogTypes();
        if (!in_array($callerClass, $enabledTypes, true)) {
            return;
        }

        $folder = sprintf('CTBC/%s', trim($callerClass, '/'));
        $this->logger->writeLog($message, $folder);
    }
}

