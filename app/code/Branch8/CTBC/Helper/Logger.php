<?php
namespace Branch8\CTBC\Helper;


class Logger
{
    /**
     * Update Monolog handler file path.
     *
     * Note: CTBC module no longer uses this for primary logging, but it is kept
     * for backward compatibility with existing injections/usages.
     *
     * @param mixed $logger Monolog logger instance.
     * @param string $path Path suffix under module log directory.
     * @return mixed Updated logger instance.
     */
    public function setPath($logger, $path){
        $handlers = $logger->getHandlers();

        foreach ($handlers as $handler) {
            $logPath = $handler->logpath;
            $filename = \Branch8\CTBC\Logger\Writer::MODULE_DIR. $path. date("Y_m_d") . '.log';
            $filepath = $logPath . $filename;

            $handler->url = $filepath;
            $handler->cutomfileName = $filepath;
        }

        return $logger;
    }
}
