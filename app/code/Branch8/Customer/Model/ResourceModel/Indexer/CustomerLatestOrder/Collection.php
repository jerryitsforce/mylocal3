<?php

namespace Branch8\Customer\Model\ResourceModel\Indexer\CustomerLatestOrder;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'row_id';
    protected $_eventPrefix = 'customer_latest_search_data_index';
    protected $_eventObject = 'customer_latest_search_data_index_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\Customer\Model\Indexer\CustomerLatestOrder',
            'Branch8\Customer\Model\ResourceModel\Indexer\CustomerLatestOrder');
    }
}
