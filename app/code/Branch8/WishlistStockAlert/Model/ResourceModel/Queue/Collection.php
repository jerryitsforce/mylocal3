<?php

namespace Branch8\WishlistStockAlert\Model\ResourceModel\Queue;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'queue_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WishlistStockAlert\Model\Queue::class,
            \Branch8\WishlistStockAlert\Model\ResourceModel\Queue::class
        );
    }
}
