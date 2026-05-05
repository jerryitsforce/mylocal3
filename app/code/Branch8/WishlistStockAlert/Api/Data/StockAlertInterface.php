<?php
namespace Branch8\WishlistStockAlert\Api\Data;

interface StockAlertInterface
{
    const ALERT_ID = 'alert_id';
    const WISHLIST_ITEM_ID = 'wishlist_item_id';
    const CUSTOMER_ID = 'customer_id';
    const COMBO = 'combo';
    const PRODUCT_ID = 'product_id';
    const WAS_OUT_OF_STOCK_WHEN_ADDED = 'was_out_of_stock_when_added';
    const NOTIFICATION_SENT = 'notification_sent';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const NOTIFIED_AT = 'notified_at';

    /**
     * @return int
     */
    public function getAlertId();

    /**
     * @param int $alertId
     * @return StockAlertInterface
     */
    public function setAlertId($alertId);

    /**
     * @return int
     */
    public function getWishlistItemId();

    /**
     * @param int $wishlistItemId
     * @return StockAlertInterface
     */
    public function setWishlistItemId($wishlistItemId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     * @return StockAlertInterface
     */
    public function setCustomerId($customerId);

    /**
     * @return int
     */
    public function getProductId();

    /**
     * @return string|null
     */
    public function getCombo();

    /**
     * @param int $productId
     * @return StockAlertInterface
     */
    public function setCombo(string $combo);

    /**
     * @param int $productId
     * @return StockAlertInterface
     */
    public function setProductId($productId);

    /**
     * @return int
     */
    public function getWasOutOfStockWhenAdded();

    /**
     * @param int $wasOos
     * @return StockAlertInterface
     */
    public function setWasOutOfStockWhenAdded($wasOos);

    /**
     * @return int
     */
    public function getNotificationSent();

    /**
     * @param int $sent
     * @return StockAlertInterface
     */
    public function setNotificationSent($sent);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return StockAlertInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return StockAlertInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string
     */
    public function getNotifiedAt();

    /**
     * @param string $notifiedAt
     * @return StockAlertInterface
     */
    public function setNotifiedAt($notifiedAt);
}
