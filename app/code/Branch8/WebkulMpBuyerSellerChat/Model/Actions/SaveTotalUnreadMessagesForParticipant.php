<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Laminas\Db\Sql\Select;
use Magento\Framework\App\ResourceConnection;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

class SaveTotalUnreadMessagesForParticipant
{
    private ResourceConnection $resourceConnection;
    private CustomLogger $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection
        , CustomLogger  $logger
    )
    {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $conversationId
     * @param int $profileId
     * @param string $profileUniqueId
     * @return int
     */
    public function execute(
        int    $conversationId,
        int    $profileId,
        string $profileUniqueId,
        bool   $isNewMessage = false
    )
    {
        /**
         * @TODO  Use Mysql Trigger
         */
        $select = $this->resourceConnection->getConnection()->select();
        $sum = new \Zend_Db_Expr('COUNT(*)');
        $select->reset(Select::COLUMNS)
            ->from('marketplace_chat_history',
                ['total' => $sum]
            )->join('marketplace_chat_message_recipient',
                'marketplace_chat_history.entity_id = marketplace_chat_message_recipient.message_chat_history_id')
            ->where('marketplace_chat_history.conversation_id = ? ',
                $conversationId
            )->where('marketplace_chat_message_recipient.is_read = ? ', 0)
            ->where('marketplace_chat_message_recipient.receiver_unique_id = ? ', $profileUniqueId);
        $total = (int)$this->resourceConnection->getConnection()->fetchOne($select);
        $lastUnreadMessageData = $this->getLastUnreadMessagesForParticipant($conversationId, $profileUniqueId);
        $lastUnreadMessage = 0;
        $lastUnreadReceiveAt = null;
        if ($lastUnreadMessageData) {
            $lastUnreadMessage = (int)$lastUnreadMessageData['last_unread_message'];
            $lastUnreadReceiveAt = $lastUnreadMessageData['last_unread_received_at'];
        }
        $where = [
            'conversation_id = ? ' => $conversationId,
            'profile_id = ? ' => $profileId
        ];

        $update = [
            'total_unread_messages' => $total,
            'last_unread_message' => $lastUnreadMessage,
            'last_unread_received_at' => $lastUnreadReceiveAt,
        ];
        /*     $update['retried'] = 0;*/
        if ($total === 0) {
            $update['last_unread_message'] = null;
            $update['last_unread_received_at'] = null;
        }
        if ($isNewMessage) {
            $update['mail_sent'] = 0;
            $update['retried'] = 0;
        }
        $this->resourceConnection->getConnection()->update(
            'marketplace_chat_participant',
            $update,
            $where
        );
        return (int)$total;
    }

    /**
     * @param int $conversationId
     * @param string $profileUniqueId
     * @return mixed
     */
    private function getLastUnreadMessagesForParticipant(
        int    $conversationId,
        string $profileUniqueId
    )
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->reset(Select::COLUMNS)
            ->from('marketplace_chat_history',
                [
                    'last_unread_message' => 'marketplace_chat_history.entity_id',
                    'last_unread_received_at' => 'marketplace_chat_history.date',
                ]
            )->join('marketplace_chat_message_recipient',
                'marketplace_chat_history.entity_id = marketplace_chat_message_recipient.message_chat_history_id')
            ->where('marketplace_chat_history.conversation_id = ? ',
                $conversationId
            )->where('marketplace_chat_message_recipient.is_read = ? ', 0)
            ->where('marketplace_chat_message_recipient.receiver_unique_id = ? ', $profileUniqueId)
            ->order('marketplace_chat_history.date DESC');
        return $this->resourceConnection->getConnection()->fetchRow($select);
    }
}
