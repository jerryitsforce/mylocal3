<?php

namespace Branch8\CatalogRule\Model;

class CatalogRuleApproval extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'catalogrule_approval';

    protected $_cacheTag = 'catalogrule_approval';

    protected $_eventPrefix = 'catalogrule_approval';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval');
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