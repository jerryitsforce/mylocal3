<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Cron;

use Branch8\Catalog\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class SetProfileIsDone
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param Config $config
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        LoggerInterface    $logger,
        Config             $config,
        ResourceConnection $resourceConnection
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute the cron job to clean old product change history logs.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $query = "UPDATE branch8_order_export_profile p
SET p.status = 'done'
WHERE p.status != 'done'
AND NOT EXISTS (
    SELECT 1
    FROM branch8_order_export_profile_batches c
    WHERE c.parent_id = p.entity_id
    AND c.batch_status != 'done'
);";
        $this->resourceConnection->getConnection()->query($query);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'exceptionlog')){
                $this->logger->error('Error to update profile status query: ' . $e->getMessage());
            }
        }
    }
}
