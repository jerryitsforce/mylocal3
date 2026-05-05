<?php

namespace Branch8\CustomNotification\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\DriverPool;

class OneIdFileUpLoader extends \Magento\Framework\File\Uploader
{
    private mixed $filesystem;

    /**
     * @param string $fileId
     * @param Mime|null $fileMime
     * @param DirectoryList|null $directoryList
     * @param DriverPool|null $driverPool
     * @param TargetDirectory|null $targetDirectory
     * @param Filesystem|null $filesystem
     */
    public function __construct(
        $fileId,
        Mime $fileMime = null,
        DirectoryList $directoryList = null,
        DriverPool $driverPool = null,
        TargetDirectory $targetDirectory = null,
        Filesystem $filesystem = null
    )
    {
        parent::__construct($fileId, $fileMime, $directoryList, $driverPool, $targetDirectory, $filesystem);
        $this->filesystem = $filesystem;
    }
}
