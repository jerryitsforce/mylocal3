<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data\ProfileNotificationInterface;
use Magento\Framework\Model\AbstractModel;

class ProfileNotification extends AbstractModel implements ProfileNotificationInterface
{
    const TYPE_ADMIN = 'admin';
    const TYPE_SELLER = 'seller';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ResourceModel\ProfileNotification::class
        );
    }

    /**
     * @return array|int|mixed|null
     */
    public function getProfileId()
    {
        return (int)$this->getData(ProfileNotificationInterface::ID);
    }

    /**
     * @return string
     */
    public function getProfileType()
    {
        return (string)$this->getData(self::PROFILE_TYPE);
    }

    /**
     * @param string $profileType
     * @return $this|ProfileNotification
     */
    public function setProfileType(string $profileType)
    {
        $this->setData(self::PROFILE_TYPE, $profileType);
        return $this;
    }

    /**
     * @return int
     */
    public function getSellerId()
    {
        return (int)$this->getData(self::SELLER_ID);
    }

    /**
     * @param int $sellerId
     * @return $this|ProfileNotification
     */
    public function setSellerId(int $sellerId)
    {
        $this->setData(self::SELLER_ID, $sellerId);
        return $this;
    }

    /**
     * @return string
     */
    public function getExecuteAt()
    {
        return (string)$this->getData(self::EXECUTED_AT);
    }

    /**
     * @param string $executeAt
     * @return $this|ProfileNotification
     */
    public function setExecuteAt(string $executeAt)
    {
        $this->setData(self::EXECUTED_AT, $executeAt);
        return $this;
    }

    /**
     * @param string $filePath
     * @return $this|ProfileNotification
     */
    public function setFilePath(string $filePath)
    {
        $this->setData(self::FILE_PATH, $filePath);
        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    /**
     * @return string
     */
    public function getPublishAt()
    {

        return (string)$this->getData(self::PUBLISH_AT);
    }

    /**
     * @param string $executeAt
     * @return $this|ProfileNotification
     */
    public function setPublishAt(string $executeAt)
    {
        $this->setData(self::PUBLISH_AT, $executeAt);
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverEmail()
    {
        return (string)$this->getData(self::RECEIVER_EMAIL);
    }

    /**
     * @param string $mail
     * @return $this|ProfileNotification
     */
    public function setReceiverEmail(string $mail)
    {
        $this->setData(self::RECEIVER_EMAIL, $mail);
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverName()
    {
        return (string)$this->getData(self::RECEIVER_NAME);
    }

    /**
     * @param string $name
     * @return $this|ProfileNotification
     */
    public function setReceiverName(string $name)
    {
        $this->setData(self::RECEIVER_NAME, $name);
        return $this;
    }

    /**
     * @param int $userId
     * @return $this|mixed
     */
    public function setUserId(int $userId)
    {
        $this->setData(self::USER_ID, $userId);
        return $this;
    }

    /**
     * @return array|mixed|null
     */
    public function getUserId()
    {
        return (int)$this->getData(self::USER_ID);
    }
}
