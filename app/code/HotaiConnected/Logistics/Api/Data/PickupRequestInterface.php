<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Api\Data;

/**
 * Pickup Request Data Interface
 */
interface PickupRequestInterface
{
    /**
     * Get order ID
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get order items
     *
     * @return array
     */
    public function getOrderItems();

    /**
     * Set order items
     *
     * @param array $items
     * @return $this
     */
    public function setOrderItems(array $items);

    /**
     * Get seller ID
     *
     * @return int
     */
    public function getSellerId();

    /**
     * Set seller ID
     *
     * @param int $sellerId
     * @return $this
     */
    public function setSellerId($sellerId);

    /**
     * Get logistics settings
     *
     * @return \HotaiConnected\Logistics\Model\LogisticsSettings
     */
    public function getLogisticsSettings();

    /**
     * Set logistics settings
     *
     * @param \HotaiConnected\Logistics\Model\LogisticsSettings $settings
     * @return $this
     */
    public function setLogisticsSettings($settings);
}
