<?php

namespace Branch8\WishlistStockAlert\Api;

use Branch8\WishlistStockAlert\Api\Data\StockAlertInterface;


interface StockAlertRepositoryInterface
{
    /**
     * @param StockAlertInterface $stockAlert
     * @return StockAlertInterface
     */
    public function save(StockAlertInterface $stockAlert);

    /**
     * @param int $alertId
     * @return StockAlertInterface
     */
    public function getById($alertId);

    /**
     * @param int $wishlistItemId
     * @return StockAlertInterface
     */
    public function getByWishlistItemId($wishlistItemId);

    /**
     * @param StockAlertInterface $stockAlert
     * @return bool
     */
    public function delete(StockAlertInterface $stockAlert);

    /**
     * @param int $alertId
     * @return bool
     */
    public function deleteById($alertId);

    /**
     * @return StockAlertInterface[]
     */
    public function getPendingAlerts();

}
