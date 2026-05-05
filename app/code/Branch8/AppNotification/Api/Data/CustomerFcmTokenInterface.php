<?php
namespace Branch8\AppNotification\Api\Data;

/**
 * Interface CustomerFcmTokenInterface
 * @api
 */
interface CustomerFcmTokenInterface
{

    /**
     * Get entity ID
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity ID
     *
     * @param int $id
     * @return $this
     */
    public function setEntityId($id);

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId);

    /**
     * Get FCM token
     *
     * @return string
     */
    public function getToken();

    /**
     * Set FCM token
     *
     * @param string $token
     * @return $this
     */
    public function setToken(string $token);

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId);

    /**
     * Get creation time
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt);

    /**
     * Get active status
     *
     * @return bool
     */
    public function getIsSubscribed();

    /**
     * Set active status
     *
     * @param bool $sSubscribed
     * @return $this
     */
    public function setIsSubscribed(bool $sSubscribed);
}
