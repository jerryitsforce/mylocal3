<?php

namespace Branch8\EventTicket\Model\ResourceModel\TicketEventTicket;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'ticket_event_ticket_collection';
    protected $_eventObject = 'ticket_event_ticket_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\EventTicket\Model\TicketEventTicket', 'Branch8\EventTicket\Model\ResourceModel\TicketEventTicket');
    }
}