<?php

namespace Branch8\HomeDeliveryOrdersAutoComplete\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

class Common
{
    const MAIN_MODULE_LOG_FOLDER = '/HomeDeliveryOrdersAutoComplete/';

    const CONFIG_PATH_TARGET_SHIPPING_METHODS = "home_delivery_orders_auto_complete/cron/target_shipping_methods_for_home_delivery_orders_auto_complete";
    const CONFIG_PATH_CREATED_AFTER_MINUTES   = "home_delivery_orders_auto_complete/cron/created_after_minutes_for_home_delivery_orders_auto_complete";

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var File */
    protected $file;

    /** @var DirectoryList */
    protected $directoryList;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->scopeConfig   = $scopeConfig;
        $this->file          = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * 取得後臺設定
     *
     * @param string $configPath
     * @return string|null
     */
    public function getConfig(string $configPath): ?string
    {
        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * 在指定資料夾(self::MAIN_MODULE_LOG_FOLDER)下寫log
     *
     * @param string $message
     * @param string $fileName
     * @param string $folderName
     * @return void
     */
    public function writeLog(string $message, string $fileName, string $folderName = ""): void
    {
        $folderPath = $this->directoryList->getPath('log') . self::MAIN_MODULE_LOG_FOLDER;

        if (!empty($folderName)) {
            $folderPath = $folderPath . $folderName;
            $folderPath = str_replace('//', '/', $folderPath);
        }

        if (!file_exists($folderPath)) {
            $this->file->mkdir($folderPath);
        }

        $fileName     = trim($fileName, '/');
        $fullFilePath = "{$folderPath}/{$fileName}";
        $fullFilePath = str_replace('//', '/', $fullFilePath);

        $writer = new \Zend_Log_Writer_Stream($fullFilePath);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message ?? "");
    }
}
