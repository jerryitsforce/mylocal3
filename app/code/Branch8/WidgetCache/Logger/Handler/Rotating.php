<?php
/**
 * Rotating file handler for Branch8 Widget Cache logs.
 */
namespace Branch8\WidgetCache\Logger\Handler;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

class Rotating extends RotatingFileHandler
{
    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param int $maxFiles Number of files to keep
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        int $maxFiles = 14
    ) {
        // Get log directory path using DirectoryList (same as Common::writeLog)
        $logPath = $directoryList->getPath('log');
        $filePath = $logPath . '/widgetcache.log';
        
        // Ensure directory exists (same as Common::writeLog)
        $logDir = dirname($filePath);
        if (!file_exists($logDir)) {
            $file->mkdir($logDir, 0755, true);
        }
        
        // Use DEBUG to allow logDebug() writes
        parent::__construct($filePath, $maxFiles, Logger::DEBUG);
    }
}

