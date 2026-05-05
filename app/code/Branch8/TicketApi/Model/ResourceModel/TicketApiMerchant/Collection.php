<?php

namespace Branch8\TicketApi\Model\ResourceModel\TicketApiMerchant;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'ticket_api_merchant_collection_prefix';
    protected $_eventObject = 'ticket_api_merchant_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\TicketApi\Model\TicketApiMerchant', 'Branch8\TicketApi\Model\ResourceModel\TicketApiMerchant');
    }
}
