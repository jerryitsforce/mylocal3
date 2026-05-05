<?php
declare(strict_types=1);

namespace Branch8\MarketplaceParentOrderRetryCancel\Cron;

use Branch8\MarketplaceParentOrderRetryCancel\Model\Config;
use Branch8\MarketplaceParentOrderRetryCancel\Model\Cron\RecreateParentOrder;
use Branch8\MarketplaceParentOrderRetryCancel\Helper\Logger as LoggerInterface;

class RecreateParentOrderCronProcedure
{
    private RecreateParentOrder $recreateParentOrder;
    private LoggerInterface $logger;
    private Config $config;

    /**
     * @param AutoRetryCancelParentOrder $autoRetryCancelParentOrder
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        RecreateParentOrder $autoRetryCancelParentOrder,
        Config                     $config,
        LoggerInterface            $logger
    )
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->recreateParentOrder = $autoRetryCancelParentOrder;
    }

    public function execute()
    {
        try {
            if (!$this->config->enable()) {
                return;
            }
            $this->recreateParentOrder->process();
        } catch (\Exception $e) {
            $this->logger->info(__('Can\'t get a file lock for queue processing process: %1', $e->getMessage()));
        }
    }
}
