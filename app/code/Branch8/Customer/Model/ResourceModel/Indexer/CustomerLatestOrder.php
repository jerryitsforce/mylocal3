<?php

namespace Branch8\Customer\Model\ResourceModel\Indexer;

use Branch8\Customer\Model\Indexer\CustomerLatestOrder\IndexStructure;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class CustomerLatestOrder extends AbstractDb
{
    /**
     * @var OrderResource
     */
    private $salesOrderResource;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        OrderResource                                     $salesOrderResource
    )
    {
        parent::__construct($context);
        $this->salesOrderResource = $salesOrderResource;
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieveIndexData(array $ids): array
    {
        $connection = $this->salesOrderResource->getConnection();
        if (empty($ids)) {
            $orderCondition = ['so.customer_id IS NOT NULL'];
        } else {
            $orderCondition = ['so.customer_id IN (?)', $ids];
        }
        $subSelect = clone $connection->select();
        $subSelect = $subSelect
            ->from(
                ['so' => $connection->getTableName('sales_order')],
                [
                    'entity_id',
                    'customer_id',
                    'increment_id',
                    'created_at',
                    'rn' => new \Zend_Db_Expr(
                        'ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY created_at DESC)'
                    )
                ]
            )
            ->where(...$orderCondition);
        $select = $connection->select()
            ->from(
                ['t' => $subSelect],
                [
                    IndexStructure::CUSTOMER_ID => 'customer_id',
                    IndexStructure::LATEST_ORDER_IDS => new \Zend_Db_Expr(
                        "GROUP_CONCAT(DISTINCT CONCAT(entity_id,':',increment_id) ORDER BY created_at DESC  SEPARATOR '||')"
                    )
                ]
            )
            ->where('rn <= ?', 5)
            ->group('customer_id');
        return (array)$connection->fetchAll($select);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('customer_orders_latest_index', 'row_id');
    }
}
