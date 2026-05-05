<?php

namespace Branch8\ProductExportRabbitMQ\Model;

use Branch8\ProductExportRabbitMQ\Api\Data\ProfileInterface;

class Profile extends \Magento\Framework\Model\AbstractModel implements ProfileInterface
{
    const TYPE_ADMIN = 'admin';
    const TYPE_SELLER = 'seller';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ResourceModel\Profile::class
        );
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
     * @return $this|Profile
     */
    public function setProfileType(string $profileType)
    {
        $this->setData(self::PROFILE_TYPE, $profileType);
        return $this;
    }

    /**
     * @return string
     */
    public function getProductIds()
    {
        return (string)$this->getData(self::PRODUCT_IDS);
    }

    /**
     * @param string $productIds
     * @return $this|Profile
     */
    public function setProductIds(string $productIds)
    {
        $this->setData(self::PRODUCT_IDS, $productIds);
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
     * @return $this|Profile
     */
    public function setExecuteAt(string $executeAt)
    {
        $this->setData(self::EXECUTED_AT, $executeAt);
        return $this;
    }

    /**
     * @param string $filePath
     * @return $this|Profile
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
     * @return $this|Profile
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
     * @return $this|Profile
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
     * @return $this|Profile
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

    /**
     * @param int $storeId
     * @return $this|mixed
     */
    public function setStoreId(int $storeId)
    {
        $this->setData(self::STORE_ID, $storeId);
        return $this;
    }

    /**
     * @return array|mixed|null
     */
    public function getStoreId()
    {
        return (int)$this->getData(self::STORE_ID);
    }
}
