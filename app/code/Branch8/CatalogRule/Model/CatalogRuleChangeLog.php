<?php

namespace Branch8\CatalogRule\Model;

class CatalogRuleChangeLog extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'catalogrule_change_log';

    protected $_cacheTag = 'catalogrule_change_log';

    protected $_eventPrefix = 'catalogrule_change_log';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\CatalogRule\Model\ResourceModel\CatalogRuleChangeLog');
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