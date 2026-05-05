<?php

namespace Branch8\GeneralNotifyTicket\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GeneralNotifyTicketRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('general_notify_ticket_record', 'record_id');
    }
}
