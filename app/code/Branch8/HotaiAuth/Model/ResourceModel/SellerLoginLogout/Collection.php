<?php

namespace Branch8\HotaiAuth\Model\ResourceModel\SellerLoginLogout;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'log_id';
    protected $_eventPrefix = 'branch_custom_customer_log_collection';
    protected $_eventObject = 'branch_custom_customer_log_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\HotaiAuth\Model\SellerLoginLogout', 'Branch8\HotaiAuth\Model\ResourceModel\SellerLoginLogout');
    }
}