<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageDataInterface;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

/**
 * Save message V2
 */
class MessageStateTrackingManager
{
    const READ = 1;
    const UNREAD = 0;
    private \Magento\Framework\App\ResourceConnection $resource;
    private CustomLogger $logger;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param CustomLogger $logger
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        CustomLogger                           $logger
    )
    {
        $this->logger = $logger;
        $this->resource = $resource;
    }

    /**
     * @param MessageDataInterface $messageData
     * @param $state
     * @return $this
     */
    public function setState(MessageDataInterface $messageData, $state)
    {
        $data = [
            'receiver_unique_id' => $messageData->getReceiverUniqueId(),
            'message_chat_history_id' => $messageData->getId(),
            'is_read' => $state
        ];
        $table = $this->resource->getTableName('marketplace_chat_message_recipient');
        $this->resource->getConnection()->insertOnDuplicate($table,
            [$data],
            ['receiver_unique_id', 'message_chat_history_id', 'is_read']
        );
        return $this;
    }

    /**
     * @param $uniqueId
     * @param $messages
     * @return true
     */
    public function checkAndMarkAsRead($uniqueId, $messages)
    {
        if (!empty($messages)) {
            $inserts = array_filter(array_map(function ($message) use ($uniqueId) {
                if ($message['messageRecipientReceiver'] === $uniqueId
                    && $message['is_read'] == self::UNREAD) {
                    return [
                        'is_read' => self::READ,
                        'message_chat_history_id' => $message['entity_id']
                    ];
                }
                return null;
            }, $messages));
            if (count($inserts)) {
                $this->logger->info(json_encode($inserts));
                $this->resource->getConnection()->insertOnDuplicate(
                    'marketplace_chat_message_recipient',
                    $inserts, ['is_read']
                );
            }
        }
        return true;
    }

    /**
     * @param $conversationId
     * @param $receiverUniqueId
     * @param $anchor
     * @return \Zend_Db_Statement_Interface
     */
    public function markMessagesAsRead($conversationId, $receiverUniqueId, $anchor = false)
    {
        $connection = $this->resource->getConnection();
        $query = 'UPDATE `marketplace_chat_message_recipient`  SET  `is_read` = ' . self::READ;
        $query .= ' WHERE `receiver_unique_id`= "' . $receiverUniqueId . '" AND  `message_chat_history_id` IN ';
        $query .= '(SELECT `entity_id` from `marketplace_chat_history` ';
        $query .= 'WHERE `conversation_id` = ' . $conversationId . ' ) ';
        if ($anchor) {
            $query .= ' AND `message_chat_history_id` < ' . $anchor;
        }
        return $connection->query($query);
    }
}
