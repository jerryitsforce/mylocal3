<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Data;

use HotaiConnected\Logistics\Api\Data\PickupRequestInterface;
use Magento\Framework\DataObject;

/**
 * Pickup Request Data Model
 */
class PickupRequest extends DataObject implements PickupRequestInterface
{
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
    public function setOrderId($orderId)
    {
        return $this->setData('order_id', $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderItems()
    {
        return $this->getData('order_items');
    }

    /**
     * @inheritDoc
     */
    public function setOrderItems(array $items)
    {
        return $this->setData('order_items', $items);
    }

    /**
     * @inheritDoc
     */
    public function getSellerId()
    {
        return $this->getData('seller_id');
    }

    /**
     * @inheritDoc
     */
    public function setSellerId($sellerId)
    {
        return $this->setData('seller_id', $sellerId);
    }

    /**
     * @inheritDoc
     */
    public function getLogisticsSettings()
    {
        return $this->getData('logistics_settings');
    }

    /**
     * @inheritDoc
     */
    public function setLogisticsSettings($settings)
    {
        return $this->setData('logistics_settings', $settings);
    }
}
