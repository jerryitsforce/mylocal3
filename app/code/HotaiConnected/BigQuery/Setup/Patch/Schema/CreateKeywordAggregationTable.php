<?php
namespace HotaiConnected\BigQuery\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\App\ResourceConnection;

class CreateKeywordAggregationTable implements SchemaPatchInterface
{
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('static_keyword_product_aggregation');
        $sklTable = $this->resource->getTableName('static_keyword_list');
        $ckTable = $this->resource->getTableName('catalog_keyword');
        $ckpTable = $this->resource->getTableName('catalog_keyword_product');

        // Drop the view if it exists
        $connection->query("DROP VIEW IF EXISTS {$tableName}");

        // Create the table if it does not exist
        $connection->query("CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `keyword` varchar(255) NOT NULL COMMENT 'Keyword',
            `product_id` int(10) unsigned NOT NULL COMMENT 'Product ID',
            PRIMARY KEY (`keyword`, `product_id`),
            KEY `IDX_PRODUCT_ID` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Static Keyword Product Aggregation Table'");

        // Initial data population (Optional, but good for immediate use)
        // This might take time, but is better than a slow view
        $connection->query("TRUNCATE TABLE {$tableName}");
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
        $connection->query($sql);

        return $this;
    }
}
