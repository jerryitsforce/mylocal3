<?php

namespace Branch8\TicketApi\Model\ResourceModel;

use Branch8\TicketApi\Model\TicketApiMerchant as Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TicketApiMerchant extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(Model::TABLE_NAME, Model::ID_FIELD_NAME);
    }
}
