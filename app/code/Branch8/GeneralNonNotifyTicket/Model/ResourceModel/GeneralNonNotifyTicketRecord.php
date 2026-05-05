<?php

namespace Branch8\GeneralNonNotifyTicket\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GeneralNonNotifyTicketRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('general_non_notify_ticket_record', 'record_id');
    }
}
