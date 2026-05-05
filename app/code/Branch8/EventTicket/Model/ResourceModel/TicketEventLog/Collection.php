<?php

namespace Branch8\EventTicket\Model\ResourceModel\TicketEvent;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'log_id';
    protected $_eventPrefix = 'ticket_event_log_collection';
    protected $_eventObject = 'ticket_event_log_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\EventTicket\Model\TicketEventLog', 'Branch8\EventTicket\Model\ResourceModel\TicketEventLog');
    }
}