<?php

namespace Branch8\Customer\Model\ResourceModel\LevelHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'branch8_level_historys_collection';
    protected $_eventObject = 'level_historys_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Customer\Model\LevelHistory', 'Branch8\Customer\Model\ResourceModel\LevelHistory');
    }
}