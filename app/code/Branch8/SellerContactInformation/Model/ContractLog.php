<?php

namespace Branch8\SellerContactInformation\Model;

class ContractLog extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'seller_contract_log';

    protected $_cacheTag = 'seller_contract_log';

    protected $_eventPrefix = 'seller_contract_log';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\SellerContactInformation\Model\ResourceModel\ContractLog');
    }

    /**
     * @return string[]
     */
    public function getIdentities(){
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return int
     */
    public function getDefaultValues(){
        $values = 1;

        return $values;
    }

}