<?php

namespace Branch8\WishlistStockAlert\Model;

use Magento\Framework\Model\AbstractModel;
use Branch8\WishlistStockAlert\Api\Data\StockAlertInterface;

class StockAlert extends AbstractModel implements StockAlertInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Branch8\WishlistStockAlert\Model\ResourceModel\StockAlert::class);
    }

    public function getAlertId()
    {
        return $this->getData(self::ALERT_ID);
    }

    public function setAlertId($alertId)
    {
        return $this->setData(self::ALERT_ID, $alertId);
    }

    public function getWishlistItemId()
    {
        return $this->getData(self::WISHLIST_ITEM_ID);
    }

    public function setWishlistItemId($wishlistItemId)
    {
        return $this->setData(self::WISHLIST_ITEM_ID, $wishlistItemId);
    }

    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @return void
     */
    public function getCombo()
    {
        return $this->getData(self::COMBO);
    }

    /**
     * @param string $combo
     * @return $this|StockAlertInterface
     */
    public function setCombo(string $combo)
    {
       return $this->setData(self::COMBO, $combo);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * @param $productId
     * @return StockAlertInterface|StockAlert
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getWasOutOfStockWhenAdded()
    {
        return $this->getData(self::WAS_OUT_OF_STOCK_WHEN_ADDED);
    }

    public function setWasOutOfStockWhenAdded($wasOos)
    {
        return $this->setData(self::WAS_OUT_OF_STOCK_WHEN_ADDED, $wasOos);
    }

    public function getNotificationSent()
    {
        return $this->getData(self::NOTIFICATION_SENT);
    }

    public function setNotificationSent($sent)
    {
        return $this->setData(self::NOTIFICATION_SENT, $sent);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getNotifiedAt()
    {
        return $this->getData(self::NOTIFIED_AT);
    }

    public function setNotifiedAt($notifiedAt)
    {
        return $this->setData(self::NOTIFIED_AT, $notifiedAt);
    }
}
