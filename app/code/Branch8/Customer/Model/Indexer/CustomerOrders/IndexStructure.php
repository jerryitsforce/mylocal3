<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer\CustomerOrders;

use Branch8\Customer\Model\Indexer\CustomerOrders;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure implements IndexStructureInterface
{
    public const ID_FIELD = 'row_id';
    public const CUSTOMER_ID = 'customer_id';
    public const SELLER_ID = 'seller_id';
    public const ORDER_ID = 'order_id';
    public const INCREMENT_ID = 'increment_id';
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

        self::CUSTOMER_ID => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],
        self::SELLER_ID => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],
        self::ORDER_ID => [
            'type' => Table::TYPE_INTEGER,
            'size' => 10
        ],
        self::INCREMENT_ID => [
            'type' => Table::TYPE_TEXT,
            'size' => 256
        ]
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
            'Index Row Id'
        );

        $fields = array_merge($this->fields, $fields);
        foreach ($fields as $fieldName => $fieldDefinition) {
            $columnOptions = [];
            if ($fieldName == self::CUSTOMER_ID) {
                $columnOptions = [
                    'unsigned' => true,
                    'nullable' => false
                ];
            }
            if ($fieldName == self::ORDER_ID) {
                $columnOptions = [
                    'unsigned' => true,
                    'nullable' => false
                ];
            }
            $ddlTable->addColumn(
                $fieldName,
                $fieldDefinition['type'] ?? Table::TYPE_INTEGER,
                $fieldDefinition['size'] ?? 2048,
                $columnOptions
            );
        }
        $ddlTable->addIndex(
            'unique',
            [
                self::ORDER_ID,
                self::CUSTOMER_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_UNIQUE
            ]
        );
        $ddlTable->addIndex(
            'index_customer_id',
            [
                self::CUSTOMER_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_INDEX
            ]
        );
        $ddlTable->addIndex(
            'index_seller_id',
            [
                self::SELLER_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_INDEX
            ]
        );
        $ddlTable->addIndex(
            'fulltext',
            [
                self::INCREMENT_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_FULLTEXT
            ]
        );
        $ddlTable->addForeignKey(
            $this->resource->getFkName(
                CustomerOrders::INDEXER_ID,
                self::ORDER_ID,
                'sales_order',
                'entity_id'
            ),
            self::ORDER_ID,
            $this->resource->getTableName('sales_order'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'FK foreign key'
        );
        $ddlTable->addForeignKey(
            $this->resource->getFkName(
                CustomerOrders::INDEXER_ID,
                self::CUSTOMER_ID,
                'customer_entity',
                'entity_id'
            ),
            self::CUSTOMER_ID,
            $this->resource->getTableName('customer_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'FK customer'
        );

        $connection->createTable($ddlTable);
    }
}
