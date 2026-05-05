<?php

namespace Branch8\HotaiPoint\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Branch8\HotaiPoint\Cron\SyncApiRecord;

class RemoveSyncBackupFile
{
    const LOG_FOLDER_NAME           = 'HotaiPoint/Cron/RemoveSyncBackupFile';
    const DEFAULT_REMOVE_LIMIT_DAYS = 60;
    private const DEBUG_LOG_OPTION = LogOption::LOG_REMOVE_SYNC_BACKUP_FILE;

    protected HotaiCoreCommonHelper $hotaiCoreCommonHelper;
    protected CommonHelper          $commonHelper;
    protected DirectoryList         $directoryList;
    protected Filesystem            $fileSystem;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        DirectoryList $directoryList,
        Filesystem $fileSystem
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->commonHelper          = $commonHelper;
        $this->directoryList         = $directoryList;
        $this->fileSystem            = $fileSystem;
    }

    public function execute()
    {
        $this->commonHelper->writeLogIfEnabled(
            'RemoveSyncBackupFile start.',
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        try {
            $removeLimitTimestamp = $this->getRemoveLimitTimestamp();

            $files = $this->getSyncFiles();

            $this->removeOverLimitFile($files, $removeLimitTimestamp);
        } catch (\Throwable $th) {
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    'title'   => 'RemoveSyncBackupFile execute error',
                    'message' => $th->getMessage(),
                ]),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        }

        $this->commonHelper->writeLogIfEnabled(
            'RemoveSyncBackupFile end.',
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    protected function getSyncFiles()
    {
        $files     = [];
        $baseDir   = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $targetDir = $baseDir . '/' . trim(SyncApiRecord::SYNC_BACKUP_FOLDER, '/');

        if (!is_dir($targetDir)) {
            return $files;
        }

        $filePath = glob($targetDir . '/*.txt');

        return $filePath;
    }

    protected function getRemoveLimitTimestamp(): int
    {
        $removeLimitDaysSetting = $this->commonHelper->getHotaiPointConfig($this->commonHelper::HOTAI_POINT_CONFIG_PATH_FTP_REMOVE_SYNC_BACKUP_FILE_AFTER_X_DAYS);

        $this->commonHelper->writeLogIfEnabled(
            "Remove limit days setting: {$removeLimitDaysSetting}",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $removeLimitDays = $removeLimitDaysSetting ?: self::DEFAULT_REMOVE_LIMIT_DAYS;

        $this->commonHelper->writeLogIfEnabled(
            "Final remove limit days: {$removeLimitDays}",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        return $removeLimitDays * 24 * 60 * 60;
    }

    protected function removeOverLimitFile(array $files, int $removeLimitTimestamp)
    {
        $now = time();

        foreach ($files as $file) {
            $creationTime = filectime($file);

            if (($now - $creationTime) > $removeLimitTimestamp) {
                try {
                    unlink($file);

                    $this->commonHelper->writeLogIfEnabled(
                        "Remove file: {$file}",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                } catch (\Exception $e) {
                    $this->commonHelper->writeLogIfEnabled(
                        "Error when remove file: {$file}, error message: " . $e->getMessage(),
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                }
            }
        }
    }
}
