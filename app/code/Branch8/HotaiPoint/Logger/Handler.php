<?php

namespace Branch8\HotaiPoint\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /** @var int */
    protected $loggerType = Logger::INFO;

    /** @var string */
    protected $filePath;

    /** @var DriverInterface */
    protected $filesystem;

    public function __construct(
        DriverInterface $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    public function setLoggerData($fileName)
    {
        $this->filePath = '/var/log/HotaiPoint/' . $fileName . '.log';
        parent::__construct(
            $this->filesystem,
            null,
            $this->filePath
        );
    }
}
