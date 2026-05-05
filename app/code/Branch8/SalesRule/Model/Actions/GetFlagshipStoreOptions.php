<?php

namespace Branch8\SalesRule\Model\Actions;

use Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class GetFlagshipStoreOptions implements GetSaleRuleAttributeOptionsInterface
{
    private ResourceConnection $connection;
    protected $flagshipCollectionFactory;

    /**
     *
     * @param ResourceConnection $connection
     * @param CollectionFactory $flagshipCollectionFactory
     */
    public function __construct(
        ResourceConnection $connection,
        CollectionFactory $flagshipCollectionFactory
    ){
        $this->connection = $connection;
        $this->flagshipCollectionFactory = $flagshipCollectionFactory;
    }

    /**
     * @param $page
     * @param $limit
     * @param $filters
     * @return array
     */
    public function get($filters = [], $page = 1, $limit = 1000)
    {
        $offset = ($page - 1) * $limit;
        $collection = $this->flagshipCollectionFactory->create();
        $select = $collection->getSelect();
        $select->limit($limit, $offset);
        $select->reset('columns')->columns(
            [
                'entity_id', 'store_name'
            ]
        );
        if ($filters) {
            foreach ($filters as $filter) {
                $select->where($filter['field'], $filter['value']);
            }
        }
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['entity_id'], 'label' => $row['store_name'], 'level' => 1];
            }, $rows);
            return ['total' => $collection->getSize(), 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
