<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Api\TotalUnreadMessageInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Laminas\Db\Sql\Select;
use Magento\Framework\App\ResourceConnection;

class ReadTotalUnreadMessages implements TotalUnreadMessageInterface
{
    private ResourceConnection $resourceConnection;
    private ChatProfileRepository $chatProfileRepository;
    private ChatConversationRepository $chatConversationRepository;

    /**
     * @param ChatProfileRepository $chatProfileRepository
     * @param ChatConversationRepository $conversationRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ChatProfileRepository      $chatProfileRepository,
        ChatConversationRepository $conversationRepository,
        ResourceConnection         $resourceConnection
    )
    {
        $this->chatProfileRepository = $chatProfileRepository;
        $this->chatConversationRepository = $conversationRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $chatProfileId
     * @param string $conversationUniqueId
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $chatProfileId, string $conversationUniqueId)
    {
        $chatProfile = $this->chatProfileRepository->getByUniqueId($chatProfileId);
        $chatConversation = $this->chatConversationRepository->loadConversationByCode($conversationUniqueId);
        return $this->execute($chatProfile, $chatConversation);
    }

    /**
     * /**
     * @param ChatProfileInformation $chatProfileInformation
     * @param ChatConversation $
     * @return int
     */
    public function execute(
        ChatProfileInformation $chatProfileInformation,
        ChatConversation       $conversation
    )
    {
        $select = $this->resourceConnection->getConnection()->select();
        $sum = new \Zend_Db_Expr('COUNT(*)');
        $select->reset(Select::COLUMNS)
            ->from('marketplace_chat_history', ['total' => $sum])
            ->join('marketplace_chat_message_recipient',
                'marketplace_chat_history.entity_id = marketplace_chat_message_recipient.message_chat_history_id',
                []
            )->where('marketplace_chat_message_recipient.receiver_unique_id = ?', $chatProfileInformation->getUniqueId())
            ->where('is_read = ?', 0)
            ->where('marketplace_chat_history.conversation_id = ? ', $conversation->getId());
        return (int)$this->resourceConnection->getConnection()->fetchOne($select);
    }
}
