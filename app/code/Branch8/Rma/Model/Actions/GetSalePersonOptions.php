<?php

namespace Branch8\Rma\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetSalePersonOptions
{
    private ResourceConnection $connection;
    private $roleCollectionFactory;

    /**
     * @param ResourceConnection $connection
     * @param \Magento\Authorization\Model\ResourceModel\Role\Grid\CollectionFactory $roleCollectionFactory
     */
    public function __construct(
        ResourceConnection                                                     $connection,
        \Magento\Authorization\Model\ResourceModel\Role\Grid\CollectionFactory $roleCollectionFactory
    )
    {
        $this->roleCollectionFactory = $roleCollectionFactory;
        $this->connection = $connection;
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
        /**
         * @var $collection \Magento\Authorization\Model\ResourceModel\Role\Grid\Collection
         */
        $collection = $this->roleCollectionFactory->create();
        $select = $collection->getSelect();
        $select->limit($limit, $offset);
        if ($filters) {
            foreach ($filters as $filter) {
                $select->where($filter['field'], $filter['value']);
            }
        }
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['role_id'], 'label' => $row['role_name']];
            }, $rows);
            return ['total' => $collection->getSelectCountSql(), 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
