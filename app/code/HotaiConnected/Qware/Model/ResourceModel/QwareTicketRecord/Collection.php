<?php

namespace HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'record_id';
    protected $_eventPrefix = 'qware_ticket_record_collection_prefix';
    protected $_eventObject = 'qware_ticket_record_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('HotaiConnected\Qware\Model\QwareTicketRecord', 'HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord');
    }
}