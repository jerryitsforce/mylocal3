<?php

namespace Branch8\SellerContactInformation\Model\ResourceModel\ContractLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'seller_contract_log_collection';
    protected $_eventObject = 'seller_contract_log_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\SellerContactInformation\Model\ContractLog', 'Branch8\SellerContactInformation\Model\ResourceModel\ContractLog');
    }
}