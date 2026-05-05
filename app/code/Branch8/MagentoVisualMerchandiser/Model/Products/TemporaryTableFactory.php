<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\Products;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Create in-memory temporary table with product ids and positions
 */
class TemporaryTableFactory
{
    private const PREFIX = 'visual_merchandiser_rule_product_';
    private const COLUMN_PRODUCT_ID = 'product_id';
    private const COLUMN_POSITION = 'position';
    private const BATCH_SIZE = 10000;
    /**
     * @var Product
     */
    private $resource;

    /**
     * @param Product $resource
     */
    public function __construct(
        Product $resource
    )
    {
        $this->resource = $resource;
    }

    /**
     * @param $ruleId
     * @param array $data
     * @return string
     * @throws \Zend_Db_Exception
     */
    public function create($ruleId, array $data, $truncate = true): string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->createTable($ruleId);
        if ($connection->isTableExists($tableName) && $truncate) {
            $connection->truncateTable($tableName);
        }
        $this->populate($tableName, $data);
        return $tableName;
    }

    /**
     * @param $ruleId
     * @param array $data
     * @param $truncate
     * @return string
     */
    public function populateData($ruleId, array $data, $truncate = true): string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTable(self::PREFIX . $ruleId);
        if ($connection->isTableExists($tableName) && $truncate) {
            $connection->truncateTable($tableName);
        }
        $this->populate($tableName, $data);
        return $tableName;
    }

    /**
     * @param $ruleId
     * @return string|void
     * @throws \Zend_Db_Exception
     */
    public function createTable($ruleId)
    {
        $connection = $this->resource->getConnection();
        $tableName = self::PREFIX . $ruleId;
        if ($connection->isTableExists($tableName)) {
            return;
        }
        $table = $connection->newTable($tableName);
        $table->addColumn(
            self::COLUMN_PRODUCT_ID,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Product ID'
        );
        $table->addColumn(
            self::COLUMN_POSITION,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false],
            'Position'
        );
        $table->addIndex($tableName . '_' . 'INDEX_PRODUCT', ['product_id'], ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]);
        $connection->createTable($table);
        return $tableName;
    }

    /**
     * Populate temporary table
     *
     * @param string $table
     * @param array $data
     */
    private function populate(string $table, array $data): void
    {
        $connection = $this->resource->getConnection();
        $tmpTableName = $this->resource->getTable($table);
        foreach (array_chunk($data, self::BATCH_SIZE, true) as $chunk) {
            $insertData = [];
            foreach ($chunk as $position => $productId) {
                $insertData[] = [
                    'product_id' => $productId,
                    'position' => $position
                ];
            }
            $connection->insertOnDuplicate(
                $tmpTableName,
                $insertData,
                [
                    self::COLUMN_PRODUCT_ID,
                    self::COLUMN_POSITION
                ]
            );
        }
    }
}
