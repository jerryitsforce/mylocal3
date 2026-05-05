<?php

namespace Branch8\MarketPlaceProductDiscussion\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetProductOptions
{
    private ResourceConnection $connection;
    private $productCollectionFactory;

    /**
     * @param ResourceConnection $connection
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        ResourceConnection                                             $connection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
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
         * @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
         */
        $collection = $this->productCollectionFactory->create();
        $select = $collection->getSelect();
        $select->limit($limit, $offset);
        if ($filters) {
            foreach ($filters as $filter) {
                if (isset($filter['field'])) {
                    $select->where($filter['field'], $filter['value']);
                    continue;
                }
                if (is_array($filter)) {
                    $collection->addFieldToFilter(
                        array(
                            array('attribute' => 'name', 'like' => '%HOTAI%'),
                            array('attribute' => 'sku', 'like' => '%HOTAI%')
                        )
                    );
                }
            }
        }
        $collection->addFieldToFilter('name', ['notnull' => true]);
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['entity_id'], 'label' => $row['sku'], 'name' => $row['name']];
            }, $rows);
            return ['total' => $collection->getSize(), 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
