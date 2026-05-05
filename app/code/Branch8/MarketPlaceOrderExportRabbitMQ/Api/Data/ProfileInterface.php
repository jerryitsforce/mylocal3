<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data;

interface ProfileInterface
{
    const ID = 'entity_id';

    const PROFILE_TYPE = 'profile_type';
    const ORDER_IDS = 'order_ids';
    const META = 'meta';
    const EXECUTED_AT = 'executed_at';

    const PUBLISH_AT = 'publish_at';

    const CREATED_AT = 'created_at';

    const FILE_PATH = 'file_path';

    const RECEIVER_NAME = 'receiver_name';

    const RECEIVER_EMAIL = 'receiver_email';

    const USER_ID = 'user_id';

    const PHPID = 'phpid';

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
     * @return array
     */
    public function getOrderIds();

    /**
     * @param array $orderIds
     * @return $this
     */
    public function setOrderIds(array $orderIds);

    /**
     * @return array
     */
    public function getMeta();

    /**
     * @param array $meta
     * @return mixed
     */
    public function setMeta(array $meta);

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

    /**
     * @param string $phpId
     * @return $this
     */
    public function setPhpId(string $phpId);

    /**
     * @return string
     */
    public function getPhpId();


    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

    /**
     * @return string
     */
    public function getStatus();
}
