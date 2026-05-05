<?php

namespace Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'yoxi_ticket_record_collection_prefix';
    protected $_eventObject = 'yoxi_ticket_record_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\Yoxi\Model\YoxiTicketRecord', 'Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord');
    }
}
