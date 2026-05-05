<?php
namespace Branch8\AppNotification\Model\Data;

use Branch8\AppNotification\Api\Data\NotificationInterface;

class Notification extends \Magento\Framework\DataObject implements NotificationInterface
{
    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * @inheritDoc
     */
    public function setNotificationId($notificationId)
    {
        return $this->setData('notification_id', $notificationId);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * @inheritDoc
     */
    public function setStar($star)
    {
        return $this->setData('star', $star);
    }

    /**
     * @inheritDoc
     */
    public function setIcon($icon)
    {
        return $this->setData('icon', $icon);
    }

    /**
     * @inheritDoc
     */
    public function setNotificationType($notificationType)
    {
        return $this->setData('notification_type', $notificationType);
    }

    /**
     * @inheritDoc
     */
    public function setCondition($condition)
    {
        return $this->setData('condition', $condition);
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        return $this->setData('description', $description);
    }

    /**
     * @inheritDoc
     */
    public function setRedirectUrl($redirectUrl)
    {
        return $this->setData('redirect_url', $redirectUrl);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData('order_id', $orderId);
    }

    /**
     * @inheritDoc
     */
    public function setNotificationTypeId($notificationTypeId)
    {
        return $this->setData('notification_type_id', $notificationTypeId);
    }

    /**
     * @inheritDoc
     */
    public function setFullDescription($fullDescription)
    {
        return $this->setData('full_description', $fullDescription);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData('entity_id');
    }

    /**
     * @inheritDoc
     */
    public function getNotificationId()
    {
        return $this->getData('notification_id');
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData('status');
    }
    /**
     * @inheritDoc
     */
    public function getStar()
    {
        return $this->getData('star');
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return $this->getData('icon');
    }

    /**
     * @inheritDoc
     */
    public function getNotificationType()
    {
        return $this->getData('notification_type');
    }

    /**
     * @inheritDoc
     */
    public function getCondition()
    {
        return $this->getData('condition');
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->getData('description');
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUrl()
    {
        return $this->getData('redirect_url');
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    /**
     * @inheritDoc
     */
    public function getNotificationTypeId()
    {
        return $this->getData('notification_type_id');
    }

    /**
     * @inheritDoc
     */
    public function getFullDescription()
    {
        return $this->getData('full_description');
    }
}
