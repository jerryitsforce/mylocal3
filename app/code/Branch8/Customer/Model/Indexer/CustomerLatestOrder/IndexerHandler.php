<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer\CustomerLatestOrder;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\Search\Request\Dimension;

class IndexerHandler implements IndexerInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Batch
     */
    private $batch;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var IndexScopeResolver
     */
    private $indexScopeResolver;

    /**
     * @var IndexStructure
     */
    private $indexStructure;

    /**
     * @var array
     */
    private $data;

    /**
     * @var bool
     */
    private $isIndexTableExists = false;

    public function __construct(
        ResourceConnection $resource,
        Batch $batch,
        IndexScopeResolver $indexScopeResolver,
        IndexStructure $indexStructure,
        array $data,
        $batchSize = 50000
    ) {
        $this->resource = $resource;
        $this->batch = $batch;
        $this->indexScopeResolver = $indexScopeResolver;
        $this->indexStructure = $indexStructure;
        $this->data = $data;
        $this->batchSize = $batchSize;
    }

    public function saveIndex($dimensions, \Traversable $documents): void
    {
        $this->checkIndexTable($dimensions);
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            if (!empty($batchDocuments)) {
                $this->resource->getConnection()
                    ->insertOnDuplicate(
                        $this->getIndexTableName($dimensions),
                        array_values($batchDocuments),
                        [
                            IndexStructure::LATEST_ORDER_IDS
                        ]
                    );
            }
        }
    }

    public function deleteIndex($dimensions, \Traversable $documents): void
    {
        $this->checkIndexTable($dimensions);
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            $this->resource->getConnection()
                ->delete(
                    $this->getIndexTableName($dimensions),
                    [IndexStructure::CUSTOMER_ID . ' in (?)' => $batchDocuments]
                );
        }
    }

    public function cleanIndex($dimensions): void
    {
        $this->checkIndexTable($dimensions);
        $this->resource->getConnection()
            ->truncateTable($this->getIndexTableName($dimensions));
    }

    public function isAvailable($dimensions = []): bool
    {
        return true;
    }

    private function getIndexName(): string
    {
        return $this->data['indexer_id'];
    }

    /**
     * @param Dimension[] $dimensions
     * @return string
     */
    private function getIndexTableName(array $dimensions): string
    {
        return $this->indexScopeResolver->resolve($this->getIndexName(), $dimensions);
    }

    /**
     * @param array $dimensions
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function checkIndexTable(array $dimensions): void
    {
        if (!$this->isIndexTableExists) {
            $tableName = $this->getIndexTableName($dimensions);
            if (!$this->resource->getConnection()->isTableExists($tableName)) {
                $this->indexStructure->create($this->getIndexName(), [], $dimensions);
            }
            $this->isIndexTableExists = true;
        }
    }
}
