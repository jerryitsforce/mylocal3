<?php

namespace Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'customer_ticket_due_collection_prefix';
    protected $_eventObject = 'customer_ticket_due_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\CustomerTicketTable\Model\CustomerTicketOverDue', 'Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue');
    }
}
