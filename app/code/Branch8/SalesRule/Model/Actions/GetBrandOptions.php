<?php

namespace Branch8\SalesRule\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class GetBrandOptions implements GetSaleRuleAttributeOptionsInterface
{
    private ResourceConnection $connection;
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $connection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $connection,
        LoggerInterface    $logger
    )
    {
        $this->logger = $logger;
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
        $select = $this->connection->getConnection()->select();
        $select->from(['eaov' => 'eav_attribute_option_value'],
            ['option_id' => 'eaov.option_id', 'value' => 'eaov.value']
        )->join(['eao' => 'eav_attribute_option'], 'eao.option_id = eaov.option_id', []
        )->join(['ea' => 'eav_attribute'], 'ea.attribute_id = eao.attribute_id', [])
            ->where('ea.attribute_code = ?', 'brand')
            ->where('store_id = ?', $filters['store_id'] ?? 0)
            ->where('ea.entity_type_id = ?', 4)->order('eaov.value ASC')
            ->limit($limit, $offset);///catalog_product is 4
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
                return ['value' => $row['option_id'], 'label' => $row['value'], 'level' => 1];
            }, $rows);
            $count = $this->connection->getConnection()->fetchOne($countSelect);
            return ['total' => $count, 'options' => $options];
        }
        return ['total' => 0, 'options' => []];
    }
}
