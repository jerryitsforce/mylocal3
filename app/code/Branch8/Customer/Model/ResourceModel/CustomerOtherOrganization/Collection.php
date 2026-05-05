<?php

namespace Branch8\Customer\Model\ResourceModel\CustomerOtherOrganization;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'branch8_customer_other_organization_collection';
    protected $_eventObject = 'customer_other_organization_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Customer\Model\CustomerOtherOrganization', 'Branch8\Customer\Model\ResourceModel\CustomerOtherOrganization');
    }
}