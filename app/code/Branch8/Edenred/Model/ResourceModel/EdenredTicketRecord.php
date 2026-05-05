<?php

namespace Branch8\Edenred\Model\ResourceModel;

use Branch8\Edenred\Model\EdenredTicketRecord as EdenredTicketRecordModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class EdenredTicketRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(EdenredTicketRecordModel::TABLE_NAME, EdenredTicketRecordModel::ID_FIELD_NAME);
    }
}
