<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api\Data;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\ChatProfileInfoInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class ChatProfileInformation extends AbstractModel implements ChatProfileInfoInterface, IdentityInterface
{
    /**
     * Customer data cache tag
     */
    public const CACHE_TAG = 'mp_chat_profile_data';

    /**#@+
     * customer chat statuses
     */
    public const STATUS_BUSY = 2;
    public const STATUS_ACTIVE = 1;
    public const STATUS_DISABLED = 0;

    /**#@-*/
    /**
     * @var string
     */
    protected $_cacheTag = 'mp_chat_profile_data';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'mp_chat_profile_data';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatProfileInformation::class
        );
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getIdentifier()];
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Get customer unique ID
     *
     * @return string|null
     */
    public function getUniqueId()
    {
        return $this->getData(self::UNIQUE_ID);
    }

    /**
     * Get chat status
     *
     * @return int
     */
    public function getChatStatus()
    {
        return $this->getData(self::CHAT_STATUS);
    }

    /**
     * Get user image
     *
     * @return int|null
     */
    public function getImage()
    {
        return $this->getData(self::IMAGE);
    }

    /**
     * Get chat registration type
     *
     * @return string|null
     */
    public function getRegisteredAs()
    {
        return $this->getData(self::REGISTERED_AS);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function setUniqueId($uniqueId)
    {
        return $this->setData(self::UNIQUE_ID, $uniqueId);
    }

    /**
     * @inheritdoc
     */
    public function setChatStatus($status)
    {
        return $this->setData(self::CHAT_STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    /**
     * @inheritdoc
     */
    public function setRegisteredAs($type)
    {
        return $this->setData(self::REGISTERED_AS, $type);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getObjectId()
    {
        return $this->getData(self::OBJECT_ID);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getEntityType()
    {
        return $this->getData(self::ENTITY_TYPE);
    }

    /**
     * @param $value
     * @return ChatProfileInformation|\Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setObjectId($value)
    {
        return $this->setData(self::OBJECT_ID, $value);
    }

    /**
     * @param $value
     * @return ChatProfileInformation|\Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setEntityType($value)
    {
        return $this->setData(self::ENTITY_TYPE, $value);
    }

    /**
     * GetName
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @param $value
     * @return ChatProfileInformation|\Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setName($value)
    {
        return $this->setData(self::NAME, $value);
    }

    /**
     * @return array|mixed|null
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * @param $value
     * @return ChatProfileInfoInterface
     */
    public function setEmail($value)
    {
        return $this->setData(self::EMAIL, $value);
    }

    /**
     * @param $value
     * @return ChatProfileInformation|\Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setNickName($value)
    {
        return $this->setData(self::NICK_NAME, $value);
    }

    /**
     * @param $value
     * @return ChatProfileInformation|\Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function getNickName()
    {
        return $this->getData(self::NICK_NAME);
    }

}
