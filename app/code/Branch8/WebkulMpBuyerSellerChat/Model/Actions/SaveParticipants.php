<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class SaveParticipants
{
    private ResourceConnection $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceCOnnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param $participants
     * @return true
     */
    public function execute($participants)
    {
        $connection = $this->resource->getConnection();
        $connection->insertOnDuplicate(
            $connection->getTableName('marketplace_chat_participant'),
            $participants,
            ['profile_id', 'conversation_id']
        );
        return true;
    }
}
