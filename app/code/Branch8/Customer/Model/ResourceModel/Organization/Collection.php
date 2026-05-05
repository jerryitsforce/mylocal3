<?php

namespace Branch8\Customer\Model\ResourceModel\Organization;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'branch8_customer_organization_collection';
    protected $_eventObject = 'customer_organization_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Customer\Model\Organization', 'Branch8\Customer\Model\ResourceModel\Organization');
    }
}