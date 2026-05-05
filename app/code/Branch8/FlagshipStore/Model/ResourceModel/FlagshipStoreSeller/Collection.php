<?php

namespace Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'flagship_store_seller_collection';
    protected $_eventObject = 'flagship_store_seller_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\FlagshipStore\Model\FlagshipStoreSeller', 'Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller');
    }
}