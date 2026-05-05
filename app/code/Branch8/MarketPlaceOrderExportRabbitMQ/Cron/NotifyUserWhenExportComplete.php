<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Cron;

use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\NotifyUserProcess;
use Psr\Log\LoggerInterface;

class NotifyUserWhenExportComplete
{
    /**
     * @var NotifyUserProcess
     */
    private $process;
    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        NotifyUserProcess    $notifyUserProcess,
        Config          $config,
        LoggerInterface $logger
    )
    {
        $this->process = $notifyUserProcess;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if (!$this->config->enable()) {
            return;
        }
        try {
            $this->process->process();
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'systemlog')){
                $this->logger->info(__('Can\'t get a file lock for queue processing process: %1', $e->getMessage()));
            }
        }
    }
}
