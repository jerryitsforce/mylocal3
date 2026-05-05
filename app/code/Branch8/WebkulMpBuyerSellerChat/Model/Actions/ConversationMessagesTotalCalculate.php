<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Laminas\Db\Sql\Select;
use Magento\Framework\App\ResourceConnection;

class ConversationMessagesTotalCalculate
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
     * @param int $conversationId
     * @return int
     */
    public function execute(
        int $conversationId
    )
    {
        /**
         * @TODO  Use Mysql Trigger
         */
        $select = $this->resourceConnection->getConnection()->select();
        $sum = new \Zend_Db_Expr('COUNT(*)');
        $select->reset(Select::COLUMNS)
            ->from('marketplace_chat_history', ['total' => $sum])
            ->where('marketplace_chat_history.conversation_id = ? ', $conversationId);
        $total = (int)$this->resourceConnection->getConnection()->fetchOne($select);
        $where = ['conversation_id = ? ' => $conversationId];
        return (int)$this->resourceConnection->getConnection()->update(
            'marketplace_chat_conversation',
            ['total_messages' => $total],
            $where
        );
    }
}
