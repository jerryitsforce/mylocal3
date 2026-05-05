<?php

declare(strict_types=1);

namespace Branch8\RmaAdminUi\Model\Indexer;

use Branch8\RmaAdminUi\Model\Indexer\Resource;

use Magento\Framework\Indexer\IndexerRegistry;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class Action
{
    /**
     * @var \Branch8\RmaAdminUi\Model\Indexer\Resource
     */
    private $resource;

    /**
     * @var CustomLogger
     */
    private CustomLogger $logger;

    /**
     * @param \Branch8\RmaAdminUi\Model\Indexer\Resource $resource
     * @param CustomLogger $logger
     */
    public function __construct(
        Resource        $resource,
        CustomLogger $logger
    )
    {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param array $ids
     * @return \Generator
     */
    public function getIndexInsertIterator(array $ids = []): \Generator
    {
        try {
            foreach ($this->resource->retrieveIndexData($ids) as $data) {
                if ($index = $this->formatIndexData($data)) {
                    yield $data['id'] => $index;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function formatIndexData(array $data): array
    {
        if (empty($data['id'])) {
            return [];
        }
        return $data;
    }
}
