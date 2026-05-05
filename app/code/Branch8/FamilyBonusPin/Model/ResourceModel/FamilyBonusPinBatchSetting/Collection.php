<?php

namespace Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'setting_id';
    protected $_eventPrefix = 'family_bonus_pin_batch_setting_collection_prefix';
    protected $_eventObject = 'family_bonus_pin_batch_setting_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting', 'Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting');
    }
}
