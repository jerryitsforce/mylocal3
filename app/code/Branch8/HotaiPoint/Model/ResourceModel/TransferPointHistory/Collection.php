<?php

namespace Branch8\HotaiPoint\Model\ResourceModel\TransferPointHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'hotai_point_transfer_point_history_collection_prefix';
    protected $_eventObject = 'hotai_point_transfer_point_history_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Branch8\HotaiPoint\Model\TransferPointHistory',
            'Branch8\HotaiPoint\Model\ResourceModel\TransferPointHistory'
        );
    }
}
