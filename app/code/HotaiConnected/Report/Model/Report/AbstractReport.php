<?php

namespace HotaiConnected\Report\Model\Report;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

abstract class AbstractReport implements ReportInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Get SQL query
     *
     * @return string
     */
    abstract protected function getSql(): string;

    /**
     * Execute SQL and get data
     *
     * @return array
     */
    public function getData(): array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $result = $connection->fetchAll($this->getSql());

            $this->logger->info('Report executed successfully', [
                'report' => $this->getName(),
                'rows' => count($result)
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Report execution failed', [
                'report' => $this->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
