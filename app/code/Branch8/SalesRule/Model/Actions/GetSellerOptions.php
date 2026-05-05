<?php

namespace Branch8\SalesRule\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class GetSellerOptions implements GetSaleRuleAttributeOptionsInterface
{
    private ResourceConnection $connection;
    private \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerFactory;
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $connection
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection                                               $connection,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerFactory,
        LoggerInterface                                                  $logger
    )
    {
        $this->logger = $logger;
        $this->sellerFactory = $sellerFactory;
        $this->connection = $connection;
    }

    /**
     * @param $search
     * @param $page
     * @param $limit
     * @param $filters
     * @return array
     */
    public function get($filters = [], $page = 1, $limit = 1000)
    {
        $offset = ($page - 1) * $limit;
        /**
         * @var $collection \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
         */
        $collection = $this->sellerFactory->create();
        $select = $collection->getSelect();
        $select->where('main_table.is_seller = ? ', 1)
            ->where('main_table.seller_code IS NOT NULL')
            ->group('seller_id')
            ->order('seller_id')->limit($limit, $offset);
        $select->reset('columns')->columns(
            [
                'seller_id' => 'main_table.seller_code',
                'seller' => 'CONCAT(main_table.seller_code," ",main_table.shop_title)'
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
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['seller_id'], 'label' => $row['seller'], 'level' => 1];
            }, $rows);
            return ['total' => $collection->getSize(), 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
