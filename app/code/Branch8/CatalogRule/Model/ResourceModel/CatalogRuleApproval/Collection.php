<?php

namespace Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'catalogrule_approval_collection';
    protected $_eventObject = 'catalogrule_approval_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\CatalogRule\Model\CatalogRuleApproval', 'Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval');
    }
}