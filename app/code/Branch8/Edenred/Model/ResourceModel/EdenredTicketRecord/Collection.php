<?php

namespace Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'edenred_ticket_record_collection_prefix';
    protected $_eventObject = 'edenred_ticket_record_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\Edenred\Model\EdenredTicketRecord', 'Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord');
    }
}
