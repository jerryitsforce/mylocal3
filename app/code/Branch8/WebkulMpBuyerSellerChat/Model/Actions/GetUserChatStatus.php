<?php
namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatProfileInformation\CollectionFactory;

class GetUserChatStatus
{
    protected CollectionFactory $chatProfileInfoCollectionFactory;

    public function __construct(
        CollectionFactory $chatProfileInfoCollectionFactory
    ) {
        $this->chatProfileInfoCollectionFactory = $chatProfileInfoCollectionFactory;
    }

    public function execute($userId, $registerAs)
    {
        $onlineCollection = $this->chatProfileInfoCollectionFactory->create()
            ->addFieldToFilter('object_id', $userId)
            ->addFieldToFilter('registered_as', ['eq' => $registerAs]);
        if ($onlineCollection->getSize()) {
            return $onlineCollection->getFirstItem()['chat_status'];
        }
        return false;
    }
}
