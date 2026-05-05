<?php

namespace Branch8\HotaiPay\Logger\Command;

use Monolog\Logger;
use Magento\Framework\Filesystem\DriverInterface;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    public $fileName = '';
    
    /**
     * File name
     * @var string
     */
    public $cutomfileName = 'hotai_pay/command/';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        DriverInterface $filesystem,
        \Magento\Framework\Filesystem $corefilesystem,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->_localeDate = $localeDate;
        $corefilesystem= $corefilesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR); 
        $logpath = $corefilesystem->getAbsolutePath('log/');

        $filename = $this->getCustomFile().date("Y_m_d").'.log';
        $filepath = $logpath . $filename;
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
        return  $this->cutomfileName;
    }
}
