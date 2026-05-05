<?php

declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Model\Indexer\SalesOrderSearchData;

use Branch8\SalesOrderGrid\Model\Indexer\SalesOrderSearchData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure implements IndexStructureInterface
{
    public const ID_FIELD = 'row_id';
    public const ORDER_ID = 'order_id';
    public const ALL_ITEM_SKUS = 'all_item_skus';
    public const ALL_ITEMS_COST = 'all_items_cost';
    public const ALL_ITEMS_COMMISSION_PERCENT = 'all_items_commission_percent';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var IndexScopeResolver
     */
    private $indexScopeResolver;

    /**
     * @var array
     */
    private $fields = [

        self::ORDER_ID => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],
        self::ALL_ITEM_SKUS => [
            'type' => Table::TYPE_TEXT,
            'size' => 2048
        ],
        self::ALL_ITEMS_COST => [
            'type' => Table::TYPE_TEXT,
            'size' => '2048'
        ],
        self::ALL_ITEMS_COMMISSION_PERCENT => [
            'type' => Table::TYPE_TEXT,
            'size' => '2048'
        ],
    ];
    private $extraColumns;

    /**
     * @param ResourceConnection $resource
     * @param IndexScopeResolver $indexScopeResolver
     * @param array $extraColumns
     */
    public function __construct(
        ResourceConnection $resource,
        IndexScopeResolver $indexScopeResolver,
        array              $extraColumns = []
    )
    {
        $this->resource = $resource;
        $this->indexScopeResolver = $indexScopeResolver;
        $this->extraColumns = $extraColumns;
    }

    public function delete($index, array $dimensions = [])
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->indexScopeResolver->resolve($index, $dimensions);
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    public function create($index, array $fields, array $dimensions = [])
    {
        $connection = $this->resource->getConnection();
        $ddlTable = $connection->newTable($this->indexScopeResolver->resolve($index, $dimensions));
        $ddlTable->addColumn(
            self::ID_FIELD,
            Table::TYPE_BIGINT,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ],
            'Index Row Id'
        );

        $fields = array_merge($this->fields, $this->extraColumns, $fields);
        foreach ($fields as $fieldName => $fieldDefinition) {
            $columnOptions = [];
            if ($fieldName == self::ORDER_ID) {
                $columnOptions = [
                    'unsigned' => true,
                    'nullable' => false
                ];
            }

            $ddlTable->addColumn(
                $fieldName,
                $fieldDefinition['type'] ?? Table::TYPE_TEXT,
                $fieldDefinition['size'] ?? 2048,
                $columnOptions
            );
        }
        $ddlTable->addIndex(
            'unique',
            [
                self::ORDER_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_UNIQUE
            ]
        );
        $ddlTable->addForeignKey(
            $this->resource->getFkName(
                SalesOrderSearchData::INDEXER_ID,
                self::ORDER_ID,
                'sales_order',
                'entity_id'
            ),
            self::ORDER_ID,
            $this->resource->getTableName('sales_order'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'Sales Order Search Data Index'
        );

        $connection->createTable($ddlTable);
    }
}
