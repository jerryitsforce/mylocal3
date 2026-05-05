<?php

declare(strict_types=1);

namespace Branch8\Catalog\Cron;

use Branch8\Catalog\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Psr\Log\LoggerInterface;

class CleanProductChangeHistory
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
    ) {
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
            $monthsToKeep = $this->config->getMonthsToKeep();
            $cutoffDate = (new \DateTime())->modify("-{$monthsToKeep} months")
                ->format(DateTime::DATETIME_PHP_FORMAT);

            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('branch8_product_change_history');

            $deleted = $connection->delete($tableName, ['created_at < ?' => $cutoffDate]);

            $this->logger->info("Cleaned {$deleted} old product change logs older than {$monthsToKeep} months.");
        } catch (\Exception $e) {
            $this->logger->error('Failed to clean product change logs: ' . $e->getMessage());
        }
    }
}
