<?php

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\MessageStateTrackingManager;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageType;
use Magento\Framework\App\ResourceConnection;

class GetLastUnreadMessage
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $conversationId
     * @param $chatProfileUniqId
     * @return string
     */
    public function execute($conversationId, $chatProfileUniqId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from('marketplace_chat_history',
            ['message' => 'message', 'entity_id' => 'entity_id']
        )->join(
            'marketplace_chat_message_recipient',
            'marketplace_chat_history.entity_id = marketplace_chat_message_recipient.message_chat_history_id',
            []
        )->where(
            'conversation_id = ?',
            $conversationId
        )->where(
            'marketplace_chat_message_recipient.receiver_unique_id = ?',
            $chatProfileUniqId
        )->where(
            'is_read = ? ',
            MessageStateTrackingManager::UNREAD
        )->order('date DESC')
            ->limit(1);
        return $connection->fetchRow($select);
    }
}
