<?php

namespace Branch8\Marketplace\Model;

class CustomNotification extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'marketplace_custom_notification';
    /**
     * @var string
     */
    protected $_cacheTag = 'marketplace_custom_notification';
    /**
     * @var string
     */
    protected $_eventPrefix = 'marketplace_custom_notification';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Marketplace\Model\ResourceModel\CustomNotification');
    }

    /**
     * @return string[]
     */
    public function getIdentities(){
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}