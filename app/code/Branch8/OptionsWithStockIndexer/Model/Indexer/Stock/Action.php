<?php

declare(strict_types=1);

namespace Branch8\OptionsWithStockIndexer\Model\Indexer\Stock;

use Branch8\OptionsWithStockIndexer\Model\ResourceModel\Indexer\Stock;

use Branch8\OptionsWithStockIndexer\Helper\Logger as LoggerInterface;

class Action
{
    /**
     * @var Stock
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Stock $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Stock           $resource,
        LoggerInterface $logger
    )
    {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param array $ids customer ids
     *
     * @return \Generator
     */
    public function getIndexInsertIterator(array $ids = []): \Generator
    {
        try {
            foreach ($this->resource->retrieveIndexData($ids) as $data) {
                if ($index = $this->formatIndexData($data)) {
                    yield $data['product_id'] . $data['stock_id'] . (string)$data['combo'] => $index;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function formatIndexData(array $data): array
    {
        return $data;
    }
}
