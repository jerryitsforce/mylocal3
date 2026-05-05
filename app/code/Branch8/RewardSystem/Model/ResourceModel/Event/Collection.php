<?php
namespace Branch8\RewardSystem\Model\ResourceModel\Event;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'branch8_rewardsystem_event_collection';
    protected $_eventObject = 'branch8_rewardsystem_event_collection';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\RewardSystem\Model\Event', 'Branch8\RewardSystem\Model\ResourceModel\Event');
    }
}
