<?php

namespace Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'record_id';
    protected $_eventPrefix = 'customer_ticket_collection_prefix';
    protected $_eventObject = 'customer_ticket_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\CustomerTicketTable\Model\CustomerTicket', 'Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket');
    }
}
