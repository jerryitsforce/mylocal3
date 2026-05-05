<?php

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderStatusResolverInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority\Collection;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority\CollectionFactory;
use Magento\Sales\Model\Order;

class ParentOrderFinder
{
    private \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory;

    /**
     * @param \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory
     */
    public function __construct(
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory,
    )
    {
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
    }

    /**
     * @param Order $subOrder
     * @return \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
     */
    public function find(Order $subOrder)
    {
        /**
         * @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
         */
        $collection = $this->parentOrderCollectionFactory->create();
        $collection->getSelect()->join(
            ['relation_table' => 'sales_parent_order_children'],
            'main_table.index_id = relation_table.parent_id',
            ['main_table.index_id']
        )->where('relation_table.children_id = ? ', $subOrder->getId());
        return $collection;
    }

}
