<?php

namespace Branch8\FlagshipStore\Model;

class FlagshipStore extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'flagship_store';

    protected $_cacheTag = 'flagship_store';

    protected $_eventPrefix = 'flagship_store';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore');
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