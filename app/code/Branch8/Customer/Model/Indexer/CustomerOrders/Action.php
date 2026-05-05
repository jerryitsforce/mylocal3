<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\Indexer\CustomerOrders;

use Branch8\Customer\Model\ResourceModel\Indexer\CustomerOrders;


use Psr\Log\LoggerInterface;

class Action
{
    /**
     * @var CustomerOrders
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerOrders $customerOrders
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerOrders $customerOrders,
        LoggerInterface    $logger
    )
    {
        $this->resource = $customerOrders;
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
                    yield $data['customer_id'].$data['seller_id'].$data['order_id'] => $index;
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
            IndexStructure::SELLER_ID => (int)$data[IndexStructure::SELLER_ID],
            IndexStructure::ORDER_ID => $data[IndexStructure::ORDER_ID] ?? '',
            IndexStructure::INCREMENT_ID => $data[IndexStructure::INCREMENT_ID] ?? '',
        ];
    }
}
