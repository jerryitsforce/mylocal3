<?php

declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetSortedConversationIds
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Returns sorted conversations by IDs.
     *
     * @param array $conversationIds
     *
     * @return array
     */
    public function execute(array $conversationIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['h' => 'marketplace_chat_history'],
                [
                    'conversation_id',
                    'last_update' => 'MAX(h.date)',
                    'total_unread_messages' => new \Zend_Db_Expr('SUM(CASE WHEN r.is_read = 0 THEN 1 ELSE 0 END)')
                ]
            )
            ->joinLeft(
                ['r' => 'marketplace_chat_message_recipient'],
                'h.entity_id = r.message_chat_history_id',
                []
            )
            ->where('h.conversation_id IN (?)', $conversationIds)
            ->group('h.conversation_id');

        $historyData = $connection->fetchAll($select);

        $historyList = [];
        foreach ($historyData as $row) {
            $historyList[] = [
                'conversation_id' => $row['conversation_id'],
                'last_update' => $row['last_update'],
                'total_unread_messages' => (int)$row['total_unread_messages']
            ];
        }

        usort($historyList, function ($a, $b) {
            if ($a['total_unread_messages'] > 0 && $b['total_unread_messages'] == 0) {
                return -1;
            }
            if ($a['total_unread_messages'] == 0 && $b['total_unread_messages'] > 0) {
                return 1;
            }
            return strcmp($b['last_update'], $a['last_update']);
        });

        return array_column($historyList, 'conversation_id');
    }
}
