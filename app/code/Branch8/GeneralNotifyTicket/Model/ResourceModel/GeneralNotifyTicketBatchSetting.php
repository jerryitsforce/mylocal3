<?php

namespace Branch8\GeneralNotifyTicket\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GeneralNotifyTicketBatchSetting extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('general_notify_ticket_batch_setting', 'setting_id');
    }
}
