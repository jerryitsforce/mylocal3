<?php

declare(strict_types=1);

namespace Branch8\SalesRule\Plugin\Amasty\RulesPro\Model\ResourceModel\Indexer;

use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class OrderPlugin
{
    /**
     * @var OrderResource
     */
    private $salesOrderResource;

    public function __construct(
        OrderResource $salesOrderResource
    ) {
        $this->salesOrderResource = $salesOrderResource;
    }

    /**
     * Retrieve order data such as orders count & orders base sum for customers
     *
     * @param array $ids customer ids
     *
     * @return array data with customer_id, order count and order sum
     */
    public function aroundRetrieveIndexData($subject,\Closure $process,array $ids): array
    {
        $connection = $this->salesOrderResource->getConnection();
        if (empty($ids)) {
            $customersCondition = ['o.customer_id IS NOT NULL'];
        } else {
            $customersCondition = ['o.customer_id IN (?)', $ids];
        }

        $select = $connection->select()
            ->from(
                ['o' => $this->salesOrderResource->getMainTable()],
                ['customer_id', new \Zend_Db_Expr('COUNT(*) as c'),
                    new \Zend_Db_Expr('(SUM(o.base_grand_total) + SUM(o.point_used_total))  as s')
                ]
            )->joinInner(
                ['customer' => $this->salesOrderResource->getTable('customer_entity')],
                'customer.entity_id = o.customer_id',
                []
            )->where(
                ...$customersCondition
            )->where(
                'o.state = ?',
                \Magento\Sales\Model\Order::STATE_COMPLETE
            )->group(
                'o.customer_id'
            );
        return (array)$connection->fetchAll($select);
    }
}
