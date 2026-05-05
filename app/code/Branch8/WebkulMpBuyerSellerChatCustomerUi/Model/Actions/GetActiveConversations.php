<?php

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConverstation\Collection;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConverstation\CollectionFactory;
use Zend_Log;

class GetActiveConversations
{
    const DEFAULT = 30;
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
     * @param int $limit
     * @param string|null $sortBy
     * @param string|null $sortType
     * @return Collection
     */
    public function get(
        ChatProfileInformation $chatProfile,
        int                    $limit = null,
        string                 $sortBy = null,
        string                 $sortType = null,
    )
    {
        $profileId = (int)$chatProfile->getId();
        if (!$profileId) {
            $debugBackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);
            $this->log(print_r($debugBackTrace, true));
            $this->log(print_r($_SERVER, true));
            $this->log(print_r($_SESSION, true));
        }

        if (empty($profileId)) {
            throw new \LogicException('Profile Id cannot be empty');
        }
        /**
         * @var $collection Collection
         */
        $collection = $this->collectionFactory->create();
        $subquery = new \Zend_Db_Expr(
            '(SELECT DISTINCT p1.profile_id AS customer_profile_id,
                              p1.conversation_id,
                              p2.profile_id AS seller_profile_id
            FROM marketplace_chat_participant p1
            JOIN marketplace_chat_participant p2 ON
            p1.conversation_id = p2.conversation_id
            WHERE p1.profile_id = ' . $profileId . '
             AND p2.profile_id != ' . $profileId . ')'
        );
        $collection->getSelect()->join(
            array('t' => $subquery),
            'main_table.conversation_id = t.conversation_id',
            [
                'seller_profile_id' => 't.seller_profile_id'
            ]
        )->where('status = ? ', ChatConversation::ACTIVE)
            ->where('type = ?', ChatConversation::TYPE_SELLERCHAT);
        if ($limit) {
            $collection->getSelect()->limit($limit);
        }
        if ($sortBy) {
            $collection->getSelect()->order(
                sprintf('%s %s', $sortBy, $sortType ?: 'ASC')
            );
        }
        return $collection;
    }

    /**
     * @param $message
     * @return void
     */
    private function log($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/HTGO2-3057.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->log($message, Zend_Log::INFO);
    }
}
