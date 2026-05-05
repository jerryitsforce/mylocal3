<?php

namespace Branch8\Customer\Model\ResourceModel\Indexer;

use Branch8\Customer\Model\Indexer\CustomerOrders\IndexStructure;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class CustomerOrders extends AbstractDb
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
        $select = $connection->select()
            ->from([
                'so' => $connection->getTableName('sales_order')],
                [
                    'customer_id' => 'so.customer_id',
                    'order_id' => 'so.entity_id',
                    'increment_id' => 'so.increment_id'
                ]
            )->join(
                'customer_entity',
                'customer_entity.entity_id = so.customer_id',
                []
            )->joinLeft(
                ['mo' => 'marketplace_orders'],
                'so.entity_id=mo.order_id',
                [
                    'seller_id' => 'mo.seller_id'
                ]
            )->where(...$orderCondition);
        $data = (array)$connection->fetchAll($select);
        return $data;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('customer_latest_order_index', 'row_id');
    }
}
