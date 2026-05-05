<?php

declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Model\Indexer\SalesOrderSearchData;

use Branch8\SalesOrderGrid\Model\ResourceModel\Indexer\OrderSearchData;

use Branch8\SalesOrderGrid\Helper\Logger as CustomLogger;

/**
 * Class Action
 *
 * Handles data retrieval and formatting for the Sales Order Search Data indexer.
 */
class Action
{
    /**
     * @var OrderSearchData
     */
    private $orderResource;

    private CustomLogger $logger;

    /**
     * Action constructor.
     *
     * @param OrderSearchData $orderResource
     * @param CustomLogger $logger
     */
    public function __construct(
        OrderSearchData $orderResource,
        CustomLogger $logger
    )
    {
        $this->orderResource = $orderResource;
        $this->logger = $logger;
    }

    /**
     * Get an iterator for inserting index data.
     *
     * Yields formatted index data for the provided customer IDs.
     *
     * @param array $ids customer ids
     *
     * @return \Generator
     */
    public function getIndexInsertIterator(array $ids = []): \Generator
    {
        try {
            // Iterate through raw data from the resource model
            foreach ($this->orderResource->retrieveIndexData($ids) as $data) {
                // Format the data and yield if valid
                if ($index = $this->formatIndexData($data)) {
                    yield $data['order_id'] => $index;
                }
            }
        } catch (\Exception $exception) {
            // Log any exceptions during the data retrieval or formatting process
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Format raw data into the index structure.
     *
     * @param array $data
     * @return array
     */
    public function formatIndexData(array $data): array
    {
        // Ensure order_id exists before processing
        if (empty($data['order_id'])) {
            return [];
        }

        // Map raw data fields to IndexStructure constants
        return [
            IndexStructure::ORDER_ID => (int)$data['order_id'],
            IndexStructure::ALL_ITEM_SKUS => $data['skus'] ?? '',
            IndexStructure::ALL_ITEMS_COST => $data['costs'] ?? '',
            IndexStructure::ALL_ITEMS_COMMISSION_PERCENT => $data['commissions'] ?? ''
        ];
    }
}
