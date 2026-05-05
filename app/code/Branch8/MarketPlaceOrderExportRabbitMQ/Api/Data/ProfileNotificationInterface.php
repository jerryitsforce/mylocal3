<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data;

interface ProfileNotificationInterface
{
    const ID = 'entity_id';

    const PROFILE_TYPE = 'profile_type';
    const SELLER_ID = 'seller_id';
    const EXECUTED_AT = 'executed_at';

    const PUBLISH_AT = 'publish_at';

    const CREATED_AT = 'created_at';

    const FILE_PATH = 'file_path';

    const RECEIVER_NAME = 'receiver_name';

    const RECEIVER_EMAIL = 'receiver_email';

    const USER_ID = 'user_id';

    /**
     * @return int
     */
    public function getProfileId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id);

    /**
     * @return string
     */
    public function getProfileType();

    /**
     * @param string $profileType
     * @return $this
     */
    public function setProfileType(string $profileType);

    /**
     * @return int
     */
    public function getSellerId();

    /**
     * @param int $sellerId
     * @return $this
     */
    public function setSellerId(int $sellerId);

    /**
     * @return string
     */
    public function getExecuteAt();

    /**
     * @param string $executeAt
     * @return $this
     */
    public function setExecuteAt(string $executeAt);

    /**
     * @return string
     */
    public function getPublishAt();

    /**
     * @param string $executeAt
     * @return $this
     */
    public function setPublishAt(string $executeAt);

    /**
     * @param string $filePath
     * @return $this
     */
    public function setFilePath(string $filePath);

    /**
     * @return string
     */
    public function getFilePath();

    /**
     * @return string
     */
    public function getReceiverEmail();

    /**
     * @param string $mail
     * @return $this
     */
    public function setReceiverEmail(string $mail);

    /**
     * @return mixed
     */
    public function getReceiverName();

    /**
     * @param string $name
     * @return $this
     */
    public function setReceiverName(string $name);

    /**
     * @param int $userId
     * @return mixed
     */
    public function setUserId(int $userId);

    /**
     * @return mixed
     */
    public function getUserId();
}
