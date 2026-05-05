<?php

namespace Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'record_id';
    protected $_eventPrefix = 'branch8_hotaipoint_hotai_point_api_record_collection';
    protected $_eventObject = 'hotai_point_api_record_collection';

    protected function _construct()
    {
        $this->_init('Branch8\HotaiPoint\Model\HotaiPointApiRecord', 'Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord');
    }
}
