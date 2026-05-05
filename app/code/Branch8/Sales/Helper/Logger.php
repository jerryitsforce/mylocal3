<?php
namespace Branch8\Sales\Helper;


class Logger
{
    const MODULE_NAME = 'sales/';
    /**
     * setPath 設定 logger 檔案位置
     *
     * @param  mixed $logger
     * @param  string $path
     * @return mixed $logger
     */
    public function setPath($logger, $path){
        $handlers = $logger->getHandlers();

        foreach ($handlers as $handler) {
            $logPath = $handler->logpath;
            $filename = self::MODULE_NAME. $path. date("Y_m_d") . '.log';
            $filepath = $logPath . $filename;

            $handler->url = $filepath;
            $handler->cutomfileName = $filepath;
        }

        return $logger;
    }
}
