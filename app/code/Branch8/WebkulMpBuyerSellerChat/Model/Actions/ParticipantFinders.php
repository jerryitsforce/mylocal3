<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Find all participants base on Conversation
 */
class ParticipantFinders
{
    private ResourceConnection $resourceConnection;
    private CollectionFactory $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        CollectionFactory  $collectionFactory,
        ResourceConnection $resourceConnection
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param ChatConversation $conversation
     * @param $ignore
     * @return \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant\Collection
     */
    public function find(ChatConversation $conversation, $ignore = [])
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('conversation_id', $conversation->getId());
        if ($ignore) {
            $collection->getSelect()->where('profile_id NOT IN (?) ', $ignore);
        }
        return $collection;
    }
}
