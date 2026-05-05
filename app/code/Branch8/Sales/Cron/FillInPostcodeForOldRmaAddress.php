<?php

namespace Branch8\Sales\Cron;

use Branch8\Sales\Console\Command\FillInPostcodeForOldRmaAddress as Command;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class FillInPostcodeForOldRmaAddress
{
    const LOG_FOLDER_NAME = 'Sales/Cron/FillInPostcodeForOldRmaAddress';

    /** @var Command */
    protected $command;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        Command $command,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->command               = $command;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    public function execute()
    {
        $this->hotaiCoreCommonHelper->writeLog(
            "FillInPostcodeForOldRmaAddress Cron start.",
            self::LOG_FOLDER_NAME
        );

        try {
            $this->command->setExecuteAll(true);
            $this->command->handle();
        } catch (\Exception $e) {
            $this->hotaiCoreCommonHelper->writeLog(
                "FillInPostcodeForOldRmaAddress Cron exception: " . $e->getMessage(),
                self::LOG_FOLDER_NAME
            );
        }

        $this->hotaiCoreCommonHelper->writeLog(
            "FillInPostcodeForOldRmaAddress Cron end.",
            self::LOG_FOLDER_NAME
        );
    }
}
