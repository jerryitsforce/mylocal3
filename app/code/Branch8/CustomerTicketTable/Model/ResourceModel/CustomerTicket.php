<?php

namespace Branch8\CustomerTicketTable\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CustomerTicket extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('customer_ticket', 'record_id');
    }
}
