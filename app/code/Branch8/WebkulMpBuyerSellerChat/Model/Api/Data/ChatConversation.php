<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api\Data;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatConversationInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class ChatConversation extends AbstractModel implements ChatConversationInterface, IdentityInterface
{
    const ACTIVE = 1;
    const CLOSE = 2;
    /**
     * Message history cache tag
     */
    public const CACHE_TAG = 'mp_chat_conversation';
    /**#@-*/
    /**
     * @var string
     */
    protected $_cacheTag = 'mp_chat_conversation';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'mp_chat_conversation';
    /**
     * @var
     */
    private $totalMessages;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatConversation::class
        );
    }

    /**
     * @return int
     */
    public function getConversationId()
    {
        return (int)$this->getData(self::CONVERSATION_ID);
    }

    /**
     * @param int $value
     * @return $this|ChatConversationInterface
     */
    public function setConversationId(int $value)
    {
        $this->setData(self::CONVERSATION_ID, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getConversationUniqueId()
    {
        return (string)$this->getData(self::CONVERSATION_UNIQUE_ID);
    }

    /**
     * @param string $value
     * @return $this|ChatConversationInterface
     */
    public function setConversationUniqueId(string $value)
    {
        $this->setData(self::CONVERSATION_UNIQUE_ID, $value);
        return $this;
    }

    /**
     * @param string $value
     * @return $this|ChatConversationInterface
     */
    public function setCreatorProfileId(int $value)
    {
        $this->setData(self::CREATOR_PROFILE_ID, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCreatorProfileId()
    {
        return (string)$this->getData(self::CREATOR_PROFILE_ID);
    }

    /**
     * @param string $value
     * @return $this|ChatConversationInterface
     */
    public function setReceiverProfileId(int $value)
    {
        $this->setData(self::RECEIVER_PROFILE_ID, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverProfileId()
    {
        return (string)$this->getData(self::RECEIVER_PROFILE_ID);
    }

    /**
     * @param int $value
     * @return $this|ChatConversationInterface
     */
    public function setStatus(int $value)
    {
        $this->setData(self::STATUS, $value);
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData(self::STATUS);
    }

    /**
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return int
     */
    public function getTotalMessages()
    {
        if ($this->totalMessages === null) {
            $this->totalMessages = 0;
            $connection = $this->getResource()->getConnection();
            $select = $connection->select()
                ->from('marketplace_chat_history', array('count(*) as total'))
                ->where('conversation_id = ? ', $this->getId());
            $rows = $connection->fetchRow($select);
            $this->totalMessages = (int)($rows['total']);
        }
        return $this->totalMessages;
    }

    /**
     * setType
     * @param $value
     * @return $this|ChatConversationInterface
     */
    public function setType($value)
    {
        $this->setData(self::TYPE, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return (string)$this->getData(self::TYPE);
    }

    /**
     * @return string
     */
    public function getLastUpdate()
    {
        return (string)$this->getData(self::LAST_UPDATE);
    }
}
