<?php

namespace HotaiConnected\OpenHub\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OpenHubTicketRecord extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('openhub_ticket_record', 'record_id');
    }
}