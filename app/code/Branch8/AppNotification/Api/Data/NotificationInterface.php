<?php
namespace Branch8\AppNotification\Api\Data;

interface NotificationInterface
{
    /**
     * @param int  $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * @param int $notificationId
     * @return $this
     */
    public function setNotificationId($notificationId);

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @param int $star
     * @return $this
     */
    public function setStar($star);

    /**
     * @param string $icon
     * @return $this
     */
    public function setIcon($icon);

    /**
     * @param string $notificationType
     * @return $this
     */
    public function setNotificationType($notificationType);

    /**
     * @param string $condition
     * @return $this
     */
    public function setCondition($condition);

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * @param string $redirectUrl
     * @return $this
     */
    public function setRedirectUrl($redirectUrl);

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * @param string $notificationTypeId
     * @return $this
     */
    public function setNotificationTypeId($notificationTypeId);

    /**
     * @param string $fullDescription
     * @return $this
     */
    public function setFullDescription($fullDescription);


    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @return int
     */
    public function getNotificationId();

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @return int
     */
    public function getStar();

    /**
     * @return string
     */
    public function getIcon();

    /**
     * @return string
     */
    public function getNotificationType();

    /**
     * @return string
     */
    public function getCondition();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return string
     */
    public function getRedirectUrl();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getOrderId();

    /**
     * @return string
     */
    public function getNotificationTypeId();

    /**
     * @return string
     */
    public function getFullDescription();
}
