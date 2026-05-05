<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'mp_chat_participant_collection';
    protected $_eventObject = 'mp_chat_participant_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipant::class,
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant::class
        );
    }
}
