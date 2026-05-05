<?php

namespace Branch8\CatalogRule\Model\ResourceModel\CatalogRuleChangeLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'catalogrule_change_log_collection';
    protected $_eventObject = 'catalogrule_change_log_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\CatalogRule\Model\CatalogRuleChangeLog', 'Branch8\CatalogRule\Model\ResourceModel\CatalogRuleChangeLog');
    }
}