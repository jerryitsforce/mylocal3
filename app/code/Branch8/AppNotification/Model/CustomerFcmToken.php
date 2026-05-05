<?php

namespace Branch8\AppNotification\Model;

use Branch8\AppNotification\Api\Data\CustomerFcmTokenInterface;
use Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class CustomerFcmToken extends AbstractModel implements CustomerFcmTokenInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_fcm_token_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheriDoc
     */
    public function getEntityId()
    {
        return (int) $this->getData('entity_id');
    }

    /**
     * @inheriDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * @inheriDoc
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id') !== null ? (int) $this->getData('customer_id') : null;
    }

    /**
     * @inheriDoc
     */
    public function setCustomerId(?int $customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * @inheriDoc
     */
    public function getToken()
    {
        return (string) $this->getData('token');
    }

    /**
     * @inheriDoc
     */
    public function setToken(string $token)
    {
        return $this->setData('token', $token);
    }

    /**
     * @inheriDoc
     */
    public function getStoreId()
    {
        return (int) $this->getData('store_id');
    }

    /**
     * @inheriDoc
     */
    public function setStoreId(int $storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * @inheriDoc
     */
    public function getCreatedAt()
    {
        return (string) $this->getData('created_at');
    }

    /**
     * @inheriDoc
     */
    public function setCreatedAt(string $createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }


    /**
     * @inheriDoc
     */
    public function getIsSubscribed()
    {
        return (bool) $this->getData('is_subscribed');
    }

    /**
     * @inheriDoc
     */
    public function setIsSubscribed(bool $isSubscribed)
    {
        return $this->setData('is_subscribed', $isSubscribed);
    }

}
