<?php
namespace HotaiConnected\BigQuery\Cron;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class UpdateKeywordAggregation
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Update keyword aggregation table
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Cron job hotai_bigquery_update_keyword_aggregation started.');
        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('static_keyword_product_aggregation');
            $sklTable = $this->resource->getTableName('static_keyword_list');
            $ckTable = $this->resource->getTableName('catalog_keyword');
            $ckpTable = $this->resource->getTableName('catalog_keyword_product');

            // 1. Truncate table
            $connection->truncateTable($tableName);

            // 2. Insert fresh data
            $sql = "INSERT IGNORE INTO {$tableName} (keyword, product_id)
                    SELECT DISTINCT
                        skl.keyword,
                        ckp.product_id
                    FROM 
                        {$sklTable} skl
                    JOIN 
                        {$ckTable} ck ON ck.keyword LIKE CONCAT('%', skl.keyword, '%')
                    JOIN 
                        {$ckpTable} ckp ON ck.entity_id = ckp.keyword_id
                    WHERE
                        skl.is_active = 1";
            
            $result = $connection->query($sql);
            
            $this->logger->info('Cron job hotai_bigquery_update_keyword_aggregation finished successfully.');
        } catch (\Exception $e) {
            $this->logger->error('Error in hotai_bigquery_update_keyword_aggregation: ' . $e->getMessage());
        }
    }
}
