<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Laminas\Db\Sql\Select;
use Magento\Framework\App\ResourceConnection;

class GetProfileTotalUnreadMessages
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $chatProfileId
     * @return int
     */
    public function execute(
        string $chatProfileId
    )
    {
        $select = $this->resourceConnection->getConnection()->select();
        $sum = new \Zend_Db_Expr('COUNT(*)');
        $select->reset(Select::COLUMNS)
            ->from('marketplace_chat_history', ['total' => $sum])
            ->join('marketplace_chat_message_recipient',
                'marketplace_chat_history.entity_id = marketplace_chat_message_recipient.message_chat_history_id',
                []
            )->where('marketplace_chat_message_recipient.receiver_unique_id = ?', $chatProfileId)
            ->where('is_read = ?', 0);
        return (int)$this->resourceConnection->getConnection()->fetchOne($select);
    }
}
