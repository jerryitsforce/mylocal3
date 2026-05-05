<?php

namespace Branch8\HotaiCore\Helper;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Zend_Log;
use Zend_Log_Exception;
use Zend_Log_Writer_Stream;

class Logger
{
    /** @var DirectoryList */
    protected DirectoryList $directoryList;

    /** @var File */
    protected File $file;

    public function __construct(
        DirectoryList $directoryList,
        File $file,
    ) {
        $this->directoryList     = $directoryList;
        $this->file              = $file;
    }

    /**
     * @param  string|array  $message
     * @param  string  $fileName
     * @param  string  $folderName
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function writeLog(string|array $message, string $folderName, string $fileName = ""): void
    {
        $folderPath = $this->directoryList->getPath('log');

        if (!empty($folderName)) {
            $folderPath .= '/' . $folderName;
            $folderPath = str_replace('//', '/', $folderPath);
        }

        if (!file_exists($folderPath)) {
            $this->file->mkdir($folderPath);
        }

        if (empty($fileName)) {
            $fileName = trim(date("Y_m_d") . ".log", '/');
        } else {
            $fileName .= ".log";
            $fileName = trim($fileName, '/');
        }

        $fullFilePath = "{$folderPath}/{$fileName}";
        $fullFilePath = str_replace('//', '/', $fullFilePath);

        $message = $this->formatMessage($message);

        $writer = new Zend_Log_Writer_Stream($fullFilePath);
        $logger = new Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Format the parameters for the logger.
     *
     * @param  array|string|Arrayable|Jsonable  $message
     * @return string
     */
    protected function formatMessage(array|string|Jsonable|Arrayable $message): string
    {
        if (is_array($message)) {
            return var_export($message, true);
        }

        if ($message instanceof Jsonable) {
            return $message->toJson();
        }

        if ($message instanceof Arrayable) {
            return var_export($message->toArray(), true);
        }

        return (string) $message;
    }
}
