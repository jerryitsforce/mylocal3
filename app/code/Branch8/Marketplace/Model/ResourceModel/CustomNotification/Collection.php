<?php

namespace Branch8\Marketplace\Model\ResourceModel\CustomNotification;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    /**
     * @var string
     */
    protected $_eventPrefix = 'marketplace_custom_notification_collection';
    /**
     * @var string
     */
    protected $_eventObject = 'custom_notification_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Marketplace\Model\CustomNotification', 'MBranch8\Marketplace\Model\ResourceModel\CustomNotification');
    }
}