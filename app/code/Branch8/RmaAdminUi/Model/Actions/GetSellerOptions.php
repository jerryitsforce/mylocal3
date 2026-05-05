<?php

namespace Branch8\RmaAdminUi\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class GetSellerOptions
{
    private ResourceConnection $connection;
    private \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerFactory;
    private CustomLogger $logger;

    /**
     * @param ResourceConnection $connection
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerFactory
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection                                               $connection,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerFactory,
        CustomLogger $logger
    )
    {
        $this->sellerFactory = $sellerFactory;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @param $search
     * @param $page
     * @param $limit
     * @param $filters
     * @return array
     */
    public function get($filters = [], $page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;
        /**
         * @var $collection \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
         */
        $collection = $this->sellerFactory->create();
        $select = $collection->getSelect();
        $select->join('customer_grid_flat', 'main_table.seller_id = customer_grid_flat.entity_id', [])
            ->where('main_table.is_seller = ? ', 1)
            ->group('seller_id')->order('seller_id')->limit($limit, $offset);
        $select->reset('columns')->columns(
            [
                'seller_id' => 'main_table.seller_id',
                'seller' => 'GROUP_CONCAT(
                                 customer_grid_flat.name
                                  ORDER BY customer_grid_flat.entity_id
                                  SEPARATOR ","
                                )'
            ]
        );
        /*if ($search) {
            $select->where('customer_grid_flat.name LIKE (?)', '%' . $search . '%');
        }*/
        if ($filters) {
            foreach ($filters as $filter) {
                $select->where($filter['field'], $filter['value']);
            }
        }
        $this->logger->info('SELECT :'.$select->__toString());
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['seller_id'], 'label' => $row['seller']];
            }, $rows);
            return ['total' => $collection->getSize(), 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
