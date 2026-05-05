<?php

namespace Branch8\HotaiCancelOrder\Helper;

use Branch8\HotaiCore\Helper\DebugLog;
use Branch8\HotaiCore\Helper\Logger as HotaiCoreLogger;

class Common
{
    private const DEBUG_MODULE_NAME = 'Branch8_HotaiCancelOrder';

    /** @var HotaiCoreLogger */
    protected HotaiCoreLogger $hotaiCoreLogger;

    public function __construct(
        HotaiCoreLogger $hotaiCoreLogger
    ) {
        $this->hotaiCoreLogger = $hotaiCoreLogger;
    }

    /**
     * @param string|array $message
     * @param string $folderName Folder under var/log/
     * @param string $logOptionValue Value that matches config multiselect.
     */
    public function writeLogIfEnabled(string|array $message, string $folderName, string $logOptionValue): void
    {
        if (!DebugLog::isEnable(self::DEBUG_MODULE_NAME, $logOptionValue)) {
            return;
        }

        $this->hotaiCoreLogger->writeLog($message, $folderName);
    }
}

