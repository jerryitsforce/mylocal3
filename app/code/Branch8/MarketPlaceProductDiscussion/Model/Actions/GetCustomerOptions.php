<?php

namespace Branch8\MarketPlaceProductDiscussion\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class GetCustomerOptions
{
    private ResourceConnection $connection;
    private LoggerInterface $logger;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Grid\Collection
     */
    private \Magento\Customer\Model\ResourceModel\Grid\Collection $collection;

    /**
     * @param ResourceConnection $connection
     * @param \Magento\Customer\Model\ResourceModel\Grid\Collection $collection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection                                    $connection,
        \Magento\Customer\Model\ResourceModel\Grid\Collection $collection,
        LoggerInterface                                       $logger
    )
    {
        $this->collection = $collection;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @param $filters
     * @param $page
     * @param $limit
     * @return array
     */
    public function get($filters = [], $page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;
        $select = $this->collection->getSelect();
        $select->limit($limit, $offset)->order('entity_id ASC');
        $select->reset('columns')->columns(
            [
                'entity_id' => 'main_table.entity_id',
                'name' => 'name'
            ]
        );
        if ($filters) {
            foreach ($filters as $filter) {
                $select->where($filter['field'], $filter['value']);
            }
        }
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['entity_id'], 'label' => $row['name']];
            }, $rows);
            return ['total' => $this->collection->getSize(), 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
