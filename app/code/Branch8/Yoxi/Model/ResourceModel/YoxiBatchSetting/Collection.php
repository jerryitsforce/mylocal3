<?php

namespace Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'setting_id';
    protected $_eventPrefix = 'yoxi_batch_setting_collection_prefix';
    protected $_eventObject = 'yoxi_batch_setting_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\Yoxi\Model\YoxiBatchSetting', 'Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting');
    }
}
