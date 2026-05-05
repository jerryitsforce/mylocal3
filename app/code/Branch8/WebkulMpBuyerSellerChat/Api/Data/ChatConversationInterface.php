<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api\Data;
interface ChatConversationInterface
{
    const CONVERSATION_ID = 'conversation_id';
    const CONVERSATION_UNIQUE_ID = 'unique_id';
    const CREATOR_PROFILE_ID = 'creator_profile_id';
    const RECEIVER_PROFILE_ID = 'receiver_profile_id';

    const STATUS = 'status';

    const TYPE = 'type';

    const LAST_UPDATE = 'last_update';
    const TYPE_SELLERCHAT = 'sellerchat';
    const TYPE_DEALERCHAT = 'dealerchat';
    /**
     * @return int
     */
    public function getConversationId();

    /**
     * @param int $value
     * @return ChatConversationInterface
     */
    public function setConversationId(int $value);

    /**
     * @return string
     */
    public function getConversationUniqueId();

    /**
     * @param string $value
     * @return mixed
     */
    public function setConversationUniqueId(string $value);

    /**
     * @param string $value
     * @return ChatConversationInterface
     */
    public function setCreatorProfileId(int $value);

    /**
     * @return int
     */
    public function getCreatorProfileId();

    /**
     * @param int $value
     * @return ChatConversationInterface
     */
    public function setReceiverProfileId(int $value);

    /**
     * @return int
     */
    public function getReceiverProfileId();

    /**
     * @param int $value
     * @return ChatConversationInterface
     */
    public function setStatus(int $value);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param string $value
     * @return ChatConversationInterface
     */
    public function setType($value);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getLastUpdate();
}
