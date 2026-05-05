<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer\CustomerLatestOrder;

use Branch8\Customer\Model\Indexer\CustomerLatestOrder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure implements IndexStructureInterface
{
    public const ID_FIELD = 'row_id';
    public const CUSTOMER_ID = 'customer_id';

    public const LATEST_ORDER_IDS = 'latest_order_ids';
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
        self::LATEST_ORDER_IDS => [
            'type' => Table::TYPE_TEXT,
            'size' => 16777216
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
                self::CUSTOMER_ID,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_UNIQUE
            ]
        );
        $ddlTable->addIndex(
            'fulltext',
            [
                self::LATEST_ORDER_IDS,
            ],
            [
                'type' => AdapterInterface::INDEX_TYPE_FULLTEXT
            ]
        );
        $ddlTable->addForeignKey(
            $this->resource->getFkName(
                CustomerLatestOrder::INDEXER_ID,
                self::CUSTOMER_ID,
                'customer_entity',
                'entity_id'
            ),
            self::CUSTOMER_ID,
            $this->resource->getTableName('customer_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment(
            'Customer Search Data Index'
        );

        $connection->createTable($ddlTable);
    }
}
