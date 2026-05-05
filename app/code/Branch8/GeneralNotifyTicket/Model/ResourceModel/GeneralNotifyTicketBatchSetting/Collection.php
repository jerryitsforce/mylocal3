<?php

namespace Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketBatchSetting;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'setting_id';
    protected $_eventPrefix = 'general_notify_ticket_batch_setting_collection_prefix';
    protected $_eventObject = 'general_notify_ticket_batch_setting_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSetting', 'Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketBatchSetting');
    }
}
