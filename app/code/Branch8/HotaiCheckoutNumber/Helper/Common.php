<?php

namespace Branch8\HotaiCheckoutNumber\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;

class Common
{
    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    public function writeLogIfEnabled(
        string|array $message,
        string $folderName,
        string $logOptionValue,
        string $fileName = ""
    ): void {
        if (!HotaiCoreDebugLog::isEnable('Branch8_HotaiCheckoutNumber', $logOptionValue)) {
            return;
        }

        $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
    }
}
