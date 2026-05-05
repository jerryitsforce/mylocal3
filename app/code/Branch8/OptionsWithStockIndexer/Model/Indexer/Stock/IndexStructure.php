<?php

declare(strict_types=1);

namespace Branch8\OptionsWithStockIndexer\Model\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure implements IndexStructureInterface
{
    public const ID_FIELD = 'row_id';

    public const PRODUCT_ID = 'product_id';

    public const STOCK_ID = 'stock_id';

    public const COMBO = 'combo';

    public const SKU = 'sku';
    public const IS_SABLE = 'is_salable';

    public const QUANTITY = 'quantity';
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

        /*self::ID_FIELD => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],*/
        self::PRODUCT_ID => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],
        self::STOCK_ID => [
            'type' => Table::TYPE_SMALLINT,
            'size' => 5
        ],
        self::COMBO => [
            'type' => Table::TYPE_TEXT,
            'size' => 2048,
            'nullable' => false,
            'default' => 'NONE',
        ],
        self::SKU => [
            'type' => Table::TYPE_TEXT,
            'size' => '124'
        ],
        self::QUANTITY => [
            'type' => Table::TYPE_INTEGER,
            'size' => '10'
        ],
        self::IS_SABLE => [
            'type' => Table::TYPE_SMALLINT,
            'size' => '2048'
        ],
    ];

    public function __construct(
        ResourceConnection $resource,
        IndexScopeResolver $indexScopeResolver
    )
    {
        $this->resource = $resource;
        $this->indexScopeResolver = $indexScopeResolver;
    }

    public function delete($index, array $dimensions = [])
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->indexScopeResolver->resolve($index, $dimensions);
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    /**
     * @param $index
     * @param array $fields
     * @param array $dimensions
     * @return void
     * @throws \Zend_Db_Exception
     */
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
            'Index Product Id'
        );

        $fields = array_merge($this->fields, $fields);
        foreach ($fields as $fieldName => $fieldDefinition) {
            $columnOptions = [];
            if ($fieldName == self::ID_FIELD) {
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
                self::PRODUCT_ID,
                self::STOCK_ID,
                self::COMBO
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_UNIQUE
            ]
        );
        $ddlTable->addIndex(
            'SKU',
            [
                self::SKU,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_INDEX
            ]
        );
        $ddlTable->addIndex(
            'COMBO',
            [
                self::COMBO,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_INDEX
            ]
        );
        $ddlTable->addIndex(
            'PRODUCT_ID',
            [
                self::PRODUCT_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_INDEX
            ]
        );
        $ddlTable->addIndex(
            'STOCK_ID',
            [
                self::STOCK_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_INDEX
            ]
        );
        $connection->createTable($ddlTable);
    }
}
