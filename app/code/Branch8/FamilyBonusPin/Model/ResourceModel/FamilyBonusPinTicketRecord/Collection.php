<?php

namespace Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'family_bonus_pin_ticket_record_collection_prefix';
    protected $_eventObject = 'family_bonus_pin_ticket_record_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord', 'Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord');
    }
}
