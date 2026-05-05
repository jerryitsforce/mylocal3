<?php

namespace Branch8\HotaiAuth\Model;

class SellerLoginLogout extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'branch_custom_customer_log';

    protected $_cacheTag = 'branch_custom_customer_log';

    protected $_eventPrefix = 'branch_custom_customer_log';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\HotaiAuth\Model\ResourceModel\SellerLoginLogout');
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