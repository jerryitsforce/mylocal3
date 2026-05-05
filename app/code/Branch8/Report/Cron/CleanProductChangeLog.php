<?php

declare(strict_types=1);

namespace Branch8\Report\Cron;

use Branch8\Report\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime;
use Psr\Log\LoggerInterface;

class CleanProductChangeLog
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
     * Execute the cron job to clean old product change logs.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $daysToKeep = $this->config->getDaysToKeep();
            $cutoffDate = (new \DateTime())->modify("-{$daysToKeep} days")
                ->format(DateTime::DATETIME_PHP_FORMAT);
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('branch8_product_change_log');
            $archiveTableName = $this->resourceConnection->getTableName('branch8_product_change_log_archive');
            $fields = array_keys($this->resourceConnection->getConnection()->describeTable($archiveTableName));
            array_shift($fields);//remove entity_id field;
            $select = $this->resourceConnection->getConnection()->select()->from('branch8_product_change_log')
                ->where('created_at < ?', $cutoffDate);
            $query = $connection->insertFromSelect(
                $select,
                $archiveTableName,
                $fields,
                AdapterInterface::INSERT_ON_DUPLICATE
            );
            $this->resourceConnection->getConnection()->query($query);
            $deleted = $connection->delete($tableName, ['created_at < ?' => $cutoffDate]);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Report', 'exceptionlog')){
                $this->logger->error('Failed to clean product change logs: ' . $e->getMessage());
            }
        }
    }
}
