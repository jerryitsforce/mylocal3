<?php

namespace Branch8\TicketApi\Model\ResourceModel;

use Branch8\TicketApi\Model\TicketApiPermission as Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TicketApiPermission extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(Model::TABLE_NAME, Model::ID_FIELD_NAME);
    }
}
