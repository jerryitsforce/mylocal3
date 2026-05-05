<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer\CustomerLatestOrder;

use Branch8\Customer\Model\ResourceModel\Indexer\CustomerLatestOrder;

use Psr\Log\LoggerInterface;

class Action
{
    /**
     * @var CustomerLatestOrder
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerLatestOrder $customerSearchData
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerLatestOrder $customerSearchData,
        LoggerInterface    $logger
    )
    {
        $this->resource = $customerSearchData;
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
                    yield $data['customer_id'] => $index;
                }
            }
        } catch (\Exception $exception) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->error($exception->getMessage());
            }
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function formatIndexData(array $data): array
    {
        if (empty($data[IndexStructure::CUSTOMER_ID])) {
            return [];
        }

        return [
            IndexStructure::CUSTOMER_ID => (int)$data[IndexStructure::CUSTOMER_ID],
            IndexStructure::LATEST_ORDER_IDS => $data[IndexStructure::LATEST_ORDER_IDS] ?? '',
        ];
    }
}
