<?php

namespace Branch8\CTBC\Logger;

use Branch8\CTBC\Logger\Logger;

class Writer
{    

    const MODULE_DIR = "CTBC/";
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Writer constructor.
     *
     * @param Logger $logger Monolog logger instance.
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }
    
    /**
     * Update Monolog handler file path based on provided path suffix.
     *
     * @param string $path Path suffix under CTBC module log directory.
     * @return Logger
     */
    public function cusLogger(string $path)
    {
        $handlers = $this->logger->getHandlers();
        
        foreach ($handlers as $handler) {
            $logPath = $handler->logpath;
            $filename = self::MODULE_DIR. $path. date("Y_m_d") . '.log';
            $filepath = $logPath . $filename;

            $handler->url = $filepath;
            $handler->cutomfileName = $filepath;
        }

        return $this->logger;
    }
}
