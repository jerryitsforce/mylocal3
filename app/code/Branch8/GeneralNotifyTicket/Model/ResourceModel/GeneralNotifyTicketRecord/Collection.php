<?php

namespace Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'general_notify_ticket_ticket_record_collection_prefix';
    protected $_eventObject = 'general_notify_ticket_ticket_record_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecord', 'Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord');
    }
}
