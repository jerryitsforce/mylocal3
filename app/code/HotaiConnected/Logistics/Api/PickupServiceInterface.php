<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Api;

/**
 * Pickup Service Interface
 */
interface PickupServiceInterface
{
    /**
     * Create pickup request for order items
     *
     * @param int $orderId
     * @param int $sellerId
     * @param string $carrier
     * @param array $itemIds
     * @return \HotaiConnected\Logistics\Api\Data\PickupResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPickup($orderId, $sellerId, $carrier, array $itemIds);

    /**
     * Get pickup records by order ID
     *
     * @param int $orderId
     * @return \HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill\Collection
     */
    public function getPickupsByOrderId($orderId);

    /**
     * Get pickup record by waybill number
     *
     * @param string $waybillNumber
     * @return \HotaiConnected\Logistics\Model\LogisticsWaybill|null
     */
    public function getPickupByWaybillNumber($waybillNumber);
}
