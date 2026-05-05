<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConverstation\Collection;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConverstation\CollectionFactory;

class GetActiveConversations
{

    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param ChatProfileInformation $chatProfile
     * @param $limit
     * @param $page
     * @return Collection
     */
    public function get(ChatProfileInformation $chatProfile, $limit = 10000, $page = 1)
    {
        /**
         * @var $collection Collection
         */
        $collection = $this->collectionFactory->create();
        $offset = ($page - 1) * $limit;
        $select = $collection->getSelect();
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $columns = [
            'conversation_id' => 'conversation_id',
            'total_messages' => 'total_messages',
            'total_unread_messages' => 'total_unread_messages',
            'unique_id' => 'unique_id',
            'creator_profile_id' => 'creator_profile_id',
            'status' => 'status',
            'created_at' => 'created_at',
            'type' => 'type',
            'last_update' => 'last_update',
            'customer_profile_id' => 'cp_customer.profile_id',
            'lastUpdateUCTimestamp' => new \Zend_Db_Expr(
                "UNIX_TIMESTAMP(CONVERT_TZ(last_update, @@session.time_zone, '+00:00'))")
        ];
        $select->join(
            ['cp_seller' => 'marketplace_chat_participant'],
            'cp_seller.conversation_id = main_table.conversation_id',
            []
        )->join(
            ['cp_customer' => 'marketplace_chat_participant'],
            'cp_customer.conversation_id = main_table.conversation_id',
            []
        );
        $select->columns($columns);
        $select->where('cp_seller.profile_id = ?', $chatProfile->getId());
        $select->where('cp_customer.profile_id <> ?', $chatProfile->getId());
        $select->where('status = ? ', ChatConversation::ACTIVE)
            ->where('type = ?', ChatConversation::TYPE_SELLERCHAT)
            ->limit($limit, $offset);
        //order by
        $select->order('cp_seller.total_unread_messages DESC');
        $select->order('main_table.last_update DESC');
        return $collection;
    }
}
