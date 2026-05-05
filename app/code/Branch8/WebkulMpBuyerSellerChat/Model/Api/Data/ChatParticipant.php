<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api\Data;

use Magento\Framework\Model\AbstractModel;

class ChatParticipant extends AbstractModel
{
    const CUSTOMER = 'customer';
    const SELLER = 'seller';
    const DEALER = 'dealer';
    const LAST_REMINDER_DATE = 'last_reminder_date';
    const EXPIRE_REMINDER_DATE = 'expire_reminder_date';
    const RETRIED = 'retried';

    const MAIL_SEND = 'mail_sent';

    const LAST_READ_MESSAGE = 'last_read_message';
    const LAST_UNREAD_MESSAGE = 'last_unread_message';
    const TOTAL_UNREAD_MESSAGES = 'total_unread_messages';
    private $chatProfileInformationFactory;

    private $chatConversationFactory;

    private $chatProfileInformation;

    private $chatConversation;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ChatProfileInformationFactory $chatProfileInformationFactory
     * @param ChatConversationFactory $chatConversationFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        ChatProfileInformationFactory                           $chatProfileInformationFactory,
        ChatConversationFactory                                 $chatConversationFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],

    )
    {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->chatProfileInformationFactory = $chatProfileInformationFactory;
        $this->chatConversationFactory = $chatConversationFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant::class
        );
    }

    /**
     * @param $conversationId
     * @param $profileId
     * @return $this
     */
    public function loadByConversationAndProfileId($conversationId, $profileId)
    {
        $this->getResource()->loadByConversationAndProfileId(
            $this,
            $conversationId,
            $profileId
        );
        return $this;
    }

    /**
     * @return ChatProfileInformation
     */
    public function getChatProfileInformation()
    {
        if (!$this->chatProfileInformation) {
            $this->chatProfileInformation = $this
                ->chatProfileInformationFactory
                ->create()->load(
                    $this->getData('profile_id')
                );
        }
        return $this->chatProfileInformation;
    }

    /**
     * @return ChatConversation
     */
    public function getChatConversation()
    {
        if (!$this->chatConversation) {
            $this->chatConversation = $this
                ->chatConversationFactory
                ->create()->load(
                    $this->getData('conversation_id')
                );
        }
        return $this->chatConversation;
    }

    /**
     * @return string
     */
    public function getLastReminderDate()
    {
        return (string)$this->getData(self::LAST_REMINDER_DATE);
    }

    /**
     * @return int
     */
    public function getTotalUnreadMessages()
    {
        return (int)$this->getData(self::TOTAL_UNREAD_MESSAGES);
    }

    /**
     * @return string
     */
    public function getExpireReminderDate()
    {
        return (string)$this->getData(self::EXPIRE_REMINDER_DATE);
    }

    /**
     * @return int
     */
    public function getRetried()
    {
        return (int)$this->getData(self::RETRIED);
    }

    /**
     * @param $value
     * @return ChatParticipant
     */
    public function setRetried($value)
    {
        return $this->setData(self::RETRIED, $value);
    }

    /**
     * @return int
     */
    public function getTotalUnreadMessage()
    {
        return (int)$this->getData(self::TOTAL_UNREAD_MESSAGES);
    }

    /**
     * @param $value
     * @return ChatParticipant
     */
    public function setLastReadMessage($value)
    {
        return $this->setData(self::LAST_READ_MESSAGE, $value);
    }

    /**
     * @return int
     */
    public function getLastReadMessage()
    {
        return (int)$this->getData(self::LAST_READ_MESSAGE);
    }

    public function getLastUnReadMessage()
    {
        return (int)$this->getData(self::LAST_UNREAD_MESSAGE);
    }

    /**
     * @param $value
     * @return ChatParticipant
     */
    public function setMailSent($value)
    {
        return $this->setData(self::MAIL_SEND, $value);
    }

    /**
     * @param $value
     * @return bool
     */
    public function getMailSent()
    {
        return (bool)$this->getData(self::MAIL_SEND);
    }
}
