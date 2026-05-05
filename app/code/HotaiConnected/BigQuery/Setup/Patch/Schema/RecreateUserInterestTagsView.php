<?php
namespace HotaiConnected\BigQuery\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\App\ResourceConnection;

class RecreateUserInterestTagsView implements SchemaPatchInterface
{
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public static function getDependencies()
    {
        return [
            CreateKeywordAggregationTable::class
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $connection = $this->resource->getConnection();
        $viewName = $this->resource->getTableName('user_interest_tags');
        
        $rpvl = $this->resource->getTableName('raw_product_view_log');
        $cpe  = $this->resource->getTableName('catalog_product_entity');
        $cpei = $this->resource->getTableName('catalog_product_entity_int');
        $skpa = $this->resource->getTableName('static_keyword_product_aggregation');
        $eav  = $this->resource->getTableName('eav_attribute');

        // 動態獲取屬性 ID
        $mainCategoryAttrId = $connection->fetchOne(
            $connection->select()->from($eav, 'attribute_id')->where('attribute_code = ?', 'main_category')
        );
        $brandAttrId = $connection->fetchOne(
            $connection->select()->from($eav, 'attribute_id')->where('attribute_code = ?', 'brand')
        );

        // 如果找不到屬性 ID，給予預設值或拋出異常（這裡假設環境中已存在，若不存在則設為 0 避免 SQL 報錯但查無資料）
        $mainCategoryAttrId = $mainCategoryAttrId ?: 0;
        $brandAttrId = $brandAttrId ?: 0;

        $sql = "CREATE OR REPLACE VIEW {$viewName} AS
                -- 1. 類別 (main_category)
                SELECT 
                    log.uid,
                    log.product_id,
                    'main_category' AS tag_category,
                    CAST(val_cat.value AS CHAR) AS tag_value,
                    COUNT(*) AS interaction_count,
                    DATE(log.event_timestamp) AS active_time
                FROM {$rpvl} AS log
                INNER JOIN {$cpe} AS e ON log.product_id = e.entity_id
                INNER JOIN {$cpei} AS val_cat ON e.row_id = val_cat.row_id AND val_cat.attribute_id = {$mainCategoryAttrId}
                GROUP BY log.uid, log.product_id, tag_value, active_time

                UNION ALL

                -- 2. 品牌 (brand)
                SELECT 
                    log.uid,
                    log.product_id,
                    'brand' AS tag_category,
                    CAST(val_brand.value AS CHAR) AS tag_value,
                    COUNT(*) AS interaction_count,
                    DATE(log.event_timestamp) AS active_time
                FROM {$rpvl} AS log
                INNER JOIN {$cpe} AS e ON log.product_id = e.entity_id
                INNER JOIN {$cpei} AS val_brand ON e.row_id = val_brand.row_id AND val_brand.attribute_id = {$brandAttrId}
                GROUP BY log.uid, log.product_id, tag_value, active_time

                UNION ALL

                -- 3. 興趣關鍵字 (interest_keyword)
                SELECT 
                    log.uid,
                    log.product_id,
                    'interest_keyword' AS tag_category,
                    agg.keyword AS tag_value,
                    COUNT(*) AS interaction_count,
                    DATE(log.event_timestamp) AS active_time
                FROM {$rpvl} AS log
                INNER JOIN {$cpe} AS e ON log.product_id = e.entity_id
                INNER JOIN {$skpa} AS agg ON e.row_id = agg.product_id 
                GROUP BY log.uid, log.product_id, tag_value, active_time";

        $connection->query($sql);
        return $this;
    }
}
