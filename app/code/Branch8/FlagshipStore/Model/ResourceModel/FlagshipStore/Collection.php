<?php

namespace Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'flagship_store_collection';
    protected $_eventObject = 'flagshihp_store_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\FlagshipStore\Model\FlagshipStore', 'Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore');
    }
}