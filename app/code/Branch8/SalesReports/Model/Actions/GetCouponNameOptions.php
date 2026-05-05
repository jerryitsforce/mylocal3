<?php

namespace Branch8\SalesReports\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;
use Branch8\SalesReports\Helper\Logger as CustomLogger;

class GetCouponNameOptions implements GetOptionsInterface
{
    private ResourceConnection $connection;
    private CustomLogger $logger;
    private $storeManager;

    /**
     * @param ResourceConnection $connection
     * @param StoreManager $storeManager
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection $connection,
        StoreManager       $storeManager,
        CustomLogger       $logger
    )
    {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
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
            ['source_table' => 'salesrule_coupon_aggregated'], ['DISTINCT(rule_name)']
        )->where('source_table.rule_name IS NOT NULL')
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
        if ($rows = $this->connection->getConnection()->fetchAll($select)) {
            $options = array_map(function ($row) {
                return ['value' => $row['rule_name'], 'label' => $row['rule_name'], 'level' => 1];
            }, $rows);
            $count = $this->connection->getConnection()->fetchOne($countSelect);
            return ['total' => $count, 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
