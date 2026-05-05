<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class CleanOldNotification
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $config
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $config,
        ResourceConnection $resourceConnection
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Execute the cron job to clean old product change logs.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $daysToKeep = $this->getDaysToKeep();
            $cutoffDate = (new \DateTime())->modify("-{$daysToKeep} days")
                ->format(DateTime::DATETIME_PHP_FORMAT);
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('magenest_customer_notification');
            $deleted = $connection->delete($tableName, ['created_at < ?' => $cutoffDate]);
            $this->logger->info("Cleaned {$deleted} old customer notification older than {$daysToKeep} days.");
        } catch (\Exception $e) {
            $this->logger->error('Failed to clean customer notification: ' . $e->getMessage());
        }
    }

    /**
     * Get the number of days to keep logs from configuration.
     *
     * @return int
     */
    protected function getDaysToKeep()
    {
        $daysToKeep = $this->config->getValue(
            'magenest_notification_box/web_push_notification/days_to_keep',
            ScopeInterface::SCOPE_STORE
        );

        return $daysToKeep ?? 90;
    }
}
