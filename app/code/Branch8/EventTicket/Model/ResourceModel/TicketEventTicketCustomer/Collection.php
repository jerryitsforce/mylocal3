<?php

namespace Branch8\EventTicket\Model\ResourceModel\TicketEventTicketCustomer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'ticket_event_ticket_customer_collection';
    protected $_eventObject = 'ticket_event_ticket_customer_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\EventTicket\Model\TicketEventTicketCustomer', 'Branch8\EventTicket\Model\ResourceModel\TicketEventTicketCustomer');
    }
}