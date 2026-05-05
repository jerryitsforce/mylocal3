<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipant as ChatParticipantModel;

/**
 *
 */
class ChatParticipant extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'marketplace_chat_participant',
            'entity_id'
        );
    }

    /**
     * @param ChatParticipantModel $object
     * @param int $conversationId
     * @param int $profileId
     * @return ChatParticipantModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByConversationAndProfileId(
        ChatParticipantModel $object,
        int                  $conversationId,
        int                  $profileId
    )
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            '*'
        )->where('conversation_id = ?', $conversationId)
            ->where('profile_id = ?', $profileId);
        $row = $this->getConnection()->fetchRow($select);
        if ($row) {
            $object->setData($row);
        }
        return $object;
    }
}
