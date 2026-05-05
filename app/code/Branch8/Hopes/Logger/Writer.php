<?php

namespace Branch8\Hopes\Logger;

use Branch8\Hopes\Logger\Logger;

class Writer
{    

    const MODULE_DIR = "Hopes/";
    /**
     * logger
     *
     * @var \Branch8\Refund\Logger\Logger $logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }
    
    /**
     * cusLogger
     *
     * @param  string $path
     * @return \Branch8\Hopes\Logger\Logger
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
