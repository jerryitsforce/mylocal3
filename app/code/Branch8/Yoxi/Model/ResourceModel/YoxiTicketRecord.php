<?php

namespace Branch8\Yoxi\Model\ResourceModel;

use Branch8\Yoxi\Model\YoxiTicketRecord as YoxiTicketRecordModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class YoxiTicketRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(YoxiTicketRecordModel::TABLE_NAME, YoxiTicketRecordModel::ID_FIELD_NAME);
    }
}
