<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Model;

use Branch8\OptionsWithStockAndImages\Api\Data\B8VariationsInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class B8Variations extends AbstractExtensibleObject implements B8VariationsInterface
{
    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getComb()
    {
        return $this->_get(self::COMB);
    }

    /**
     * @inheritDoc
     */
    public function setComb($comb)
    {
        return $this->setData(self::COMB, $comb);
    }

    /**
     * @inheritDoc
     */
    public function getWeight()
    {
        return $this->_get(self::WEIGHT);
    }

    /**
     * @inheritDoc
     */
    public function setWeight($weight)
    {
        return $this->setData(self::WEIGHT, $weight);
    }

    /**
     * @inheritDoc
     */
    public function getImage()
    {
        return $this->_get(self::IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    /**
     * @inheritDoc
     */
    public function getStock()
    {
        return $this->_get(self::STOCK);
    }

    /**
     * @inheritDoc
     */
    public function setStock($stock)
    {
        return $this->setData(self::STOCK, $stock);
    }

    /**
     * @inheritDoc
     */
    public function getProductId()
    {
        return $this->_get(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getSku()
    {
        return $this->_get(self::SKU);
    }

    /**
     * @inheritDoc
     */
    public function setSku($sku)
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritDoc
     */
    public function getIsSync()
    {
        return $this->_get(self::IS_SYNC);
    }

    /**
     * @inheritDoc
     */
    public function setIsSync($isSync)
    {
        return $this->setData(self::IS_SYNC, $isSync);
    }

    public function getIsLockSku()
    {
        return $this->_get(self::IS_LOCK_SKU);
    }

    public function setIsLockSku($isLockSku)
    {
        return $this->setData(self::IS_LOCK_SKU, $isLockSku);
    }

    public function getProductItemId()
    {
        return $this->_get(self::PRODUCT_ITEM_ID);
    }

    public function setProductItemId($productItemId)
    {
        return $this->setData(self::PRODUCT_ITEM_ID, $productItemId);
    }

    public function getReadyToShipQty()
    {
        return $this->_get(self::READY_TO_SHIP_QTY);
    }

    public function setReadyToShipQty($readyToShipQty)
    {
        return $this->setData(self::READY_TO_SHIP_QTY, $readyToShipQty);
    }

    public function getCost()
    {
        return $this->_get(self::COST);
    }

    public function setCost($cost)
    {
        return $this->setData(self::COST, $cost);
    }

    public function getCostSetting()
    {
        return $this->_get(self::COST_SETTING);
    }

    public function setCostSetting($costSetting)
    {
        return $this->setData(self::COST_SETTING, $costSetting);
    }

    public function getFollowSimpleSkuCostSetting()
    {
        return $this->_get(self::FOLLOW_SIMPLE_SKU_COST_SETTING);
    }
    
    public function setFollowSimpleSkuCostSetting($followSimpleSkuCostSetting)
    {
        return $this->setData(self::FOLLOW_SIMPLE_SKU_COST_SETTING, $followSimpleSkuCostSetting);
    }

    public function getCommissionPercent()
    {
        return $this->_get(self::COMMISSION_PERCENT);
    }

    public function setCommissionPercent($commissionPercent)
    {
        return $this->setData(self::COMMISSION_PERCENT, $commissionPercent);
    }

    public function getPrice()
    {
        return $this->_get(self::PRICE);
    }

    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    public function getFollowSimpleSkuPriceSetting()
    {
        return $this->_get(self::FOLLOW_SIMPLE_SKU_PRICE_SETTING);
    }

    public function setFollowSimpleSkuPriceSetting($followSimpleSkuPriceSetting)
    {
        return $this->setData(self::FOLLOW_SIMPLE_SKU_PRICE_SETTING, $followSimpleSkuPriceSetting);
    }
}
