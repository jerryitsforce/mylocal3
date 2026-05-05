<?php

namespace Branch8\Customer\Model;

class LevelHistory extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'branch8_level_historys';

    protected $_cacheTag = 'branch8_level_historys';

    protected $_eventPrefix = 'branch8_level_historys';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Customer\Model\ResourceModel\LevelHistory');
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