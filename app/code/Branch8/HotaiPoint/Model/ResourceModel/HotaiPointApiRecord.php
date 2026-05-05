<?php

namespace Branch8\HotaiPoint\Model\ResourceModel;

class HotaiPointApiRecord extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('hotai_point_api_record', 'record_id');
    }
}
