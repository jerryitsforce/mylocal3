<?php

namespace Branch8\SalesOrderGrid\Model\ResourceModel\Indexer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class OrderSearchData
{
    /**
     * @var OrderResource
     */
    private $salesOrderResource;

    private Manager $eventManger;

    public function __construct(
        OrderResource $salesOrderResource,
        Manager       $eventManger
    )
    {
        $this->eventManger = $eventManger;
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
            $orderCondition = ['o.entity_id IS NOT NULL'];
        } else {
            $orderCondition = ['o.entity_id IN (?)', $ids];
        }
        $select = $connection->select();
        $columns = [
            'order_id' => 'o.entity_id',
            'skus' => new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT CONCAT(item_id,':',sku) ORDER BY item_id SEPARATOR '||')"),
            'costs' => new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT CONCAT(item_id,':',sku,':',base_cost) ORDER BY item_id SEPARATOR '||')"),
            'commissions' => new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT CONCAT(item_id,':',sku,':',commission_percent) ORDER BY item_id SEPARATOR '||')"),
        ];
        $transport = new DataObject(
            [
                'columns' => $columns,
                'select' => $select,
                'orderIds' => $ids,
                'conditions' => $orderCondition
            ]
        );
        $this->eventManger->dispatch('order_search_data_prepare', ['transport' => $transport]);
        $select->from(
            ['o' => $this->salesOrderResource->getMainTable()],
            $transport->getData('columns')
        )->joinInner(
            ['i' => $this->salesOrderResource->getTable('sales_order_item')],
            'o.entity_id = i.order_id',
            []
        )->where(
            ...$transport->getData('conditions')
        )->group(
            'o.entity_id'
        );

        return (array)$connection->fetchAll($select);
    }
}
