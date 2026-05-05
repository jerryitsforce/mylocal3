<?php

namespace Branch8\HotaiPoint\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TransferPointHistory extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('hotai_point_transfer_point_history', 'record_id');
    }
}
