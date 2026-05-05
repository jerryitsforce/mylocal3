<?php

namespace Branch8\SalesReports\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;
use Branch8\SalesReports\Helper\Logger as CustomLogger;

class GetCouponCodeOptions implements GetOptionsInterface
{
    private ResourceConnection $connection;
    private CustomLogger $logger;
    private StoreManager $storeManager;

    /**
     * @param ResourceConnection $connection
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection $connection,
        StoreManager       $storeManager,
        CustomLogger       $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    /**
     * @param $filters
     * @param $page
     * @param $limit
     * @return array
     */
    public function get($filters = [], $page = 1, $limit = 1000)
    {
        $filters[] = ['field' => 'store_id = ? ', 'value' => $this->storeManager->getStore()->getId()];
        $offset = ($page - 1) * $limit;
        $select = $this->connection->getConnection()->select();
        $select->from(
            ['source_table' => 'salesrule_coupon_aggregated'], ['DISTINCT(coupon_code)']
        )->where('source_table.coupon_code IS NOT NULL')
            ->limit($limit, $offset)->distinct();
        if ($filters) {
            foreach ($filters as $filter) {
                $select->where($filter['field'], $filter['value']);
            }
        }
        $countSelect = clone $select;
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
        $this->logger->info($countSelect->__toString());
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['coupon_code'], 'label' => $row['coupon_code'], 'level' => 1];
            }, $rows);
            $count = $this->connection->getConnection()->fetchOne($countSelect);
            return ['total' => $count, 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
