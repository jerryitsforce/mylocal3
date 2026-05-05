<?php

namespace Branch8\Sales\Logger;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**　@var ?string */
    public $url;
    
    /** @var string $logpath */
    public $logpath;

    /**
     * File name
     * @var string
     */
    public $fileName = '';

    /**
     * File name
     * @var string
     */
    public $cutomfileName = 'sales';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    public function __construct(
        DriverInterface $filesystem,
        Filesystem $corefilesystem
    ) {
        $corefilesystem = $corefilesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->logpath = $corefilesystem->getAbsolutePath('log/');

        $filename = $this->getCustomFile() . date("Y_m_d") . '.log';
        $filepath = $this->logpath . $filename;
        $this->cutomfileName = $filepath;
        parent::__construct(
            $filesystem,
            $filepath
        );
    }

    /**
     * getCustomFile
     *
     * @return string
     */
    public function getCustomFile()
    {
        return $this->cutomfileName;
    }
}
