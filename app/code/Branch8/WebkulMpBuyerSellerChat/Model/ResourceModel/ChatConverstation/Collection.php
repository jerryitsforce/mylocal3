<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConverstation;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'conversation_id';
    protected $_eventPrefix = 'mp_chat_converstion_collection';
    protected $_eventObject = 'mp_chat_converstion_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation::class,
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConversation::class
        );
    }

    /**
     * @param $columns
     * @return $this
     */
    public function joinParticipantTable($columns = [])
    {
        $this->getSelect()->join(
            $this->getTable('marketplace_chat_participant'),
            'main_table.conversation_id = marketplace_chat_participant.conversation_id',
            $columns
        );
        return $this;
    }
}
