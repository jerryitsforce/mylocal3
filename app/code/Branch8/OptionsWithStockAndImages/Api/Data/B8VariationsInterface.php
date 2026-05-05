<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Api\Data;

interface B8VariationsInterface
{
    const ENTITY_ID = 'entity_id';
    const COMB = 'comb';
    const WEIGHT = 'weight';
    const IMAGE = 'image';
    const STOCK = 'stock';
    const PRODUCT_ID = 'product_id';
    const SKU = 'sku';
    const IS_SYNC = 'is_sync';
    const IS_LOCK_SKU = 'is_lock_sku';
    const PRODUCT_ITEM_ID = 'product_item_id';
    const READY_TO_SHIP_QTY = 'ready_to_ship_qty';
    const COST = 'cost';
    const COST_SETTING = 'cost_setting';
    const FOLLOW_SIMPLE_SKU_COST_SETTING = 'follow_simple_sku_cost_setting';
    const COMMISSION_PERCENT = 'commission_percent';
    const PRICE = 'price';
    const FOLLOW_SIMPLE_SKU_PRICE_SETTING = 'follow_simple_sku_price_setting';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setEntityId($entityId);

    /**
     * Get comb
     * @return string|null
     */
    public function getComb();

    /**
     * Set comb
     * @param string $comb
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setComb($comb);

    /**
     * Get weight
     * @return string|null
     */
    public function getWeight();

    /**
     * Set weight
     * @param string $weight
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setWeight($weight);

    /**
     * Get image
     * @return string|null
     */
    public function getImage();

    /**
     * Set image
     * @param string $image
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setImage($image);

    /**
     * Get stock
     * @return string|null
     */
    public function getStock();

    /**
     * Set stock
     * @param string $stock
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setStock($stock);

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setProductId($productId);

    /**
     * Get sku
     * @return string|null
     */
    public function getSku();

    /**
     * Set sku
     * @param string $sku
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setSku($sku);

    /**
     * Get is_sync
     * @return string|null
     */
    public function getIsSync();

    /**
     * Set is_sync
     * @param string $isSync
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setIsSync($isSync);

    /**
     * Get is_lock_sku
     * @return string|null
     */
    public function getIsLockSku();

    /**
     * Set is_lock_sku
     * @param string $isLockSku
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setIsLockSku($isLockSku);

    /**
     * Get product_item_id
     * @return string|null
     */
    public function getProductItemId();

    /**
     * Set product_item_id
     * @param string $productItemId
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setProductItemId($productItemId);

    /**
     * Get ready_to_ship_qty
     * @return string|null
     */
    public function getReadyToShipQty();
    /**
     * Set ready_to_ship_qty
     * @param string $readyToShipQty
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setReadyToShipQty($readyToShipQty);

    /**
     * Get cost
     * @return string|null
     */
    public function getCost();

    /**
     * Set cost
     * @param string $cost
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setCost($cost);

    /**
     * Get cost_setting
     * @return string|null
     */
    public function getCostSetting();

    /**
     * Set cost_setting
     * @param string $costSetting
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setCostSetting($costSetting);

    /**
     * Get follow_simple_sku_cost_setting
     * @return string|null
     */
    public function getFollowSimpleSkuCostSetting();

    /**
     * Set follow_simple_sku_cost_setting
     * @param string $followSimpleSkuCostSetting
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setFollowSimpleSkuCostSetting($followSimpleSkuCostSetting);

    /**
     * Get commission_percent
     * @return string|null
     */
    public function getCommissionPercent();

    /**
     * Set commission_percent
     * @param string $commissionPercent
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setCommissionPercent($commissionPercent);

    /**
     * Get price
     * @return string|null
     */
    public function getPrice();
    
    /**
     * Set price
     * @param string $price
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setPrice($price);

    /**
     * Get follow_simple_sku_price_setting
     * @return string|null
     */
    public function getFollowSimpleSkuPriceSetting();

    /**
     * Set follow_simple_sku_price_setting
     * @param string $followSimpleSkuPriceSetting
     * @return \Branch8\OptionsWithStockAndImages\B8Variations\Api\Data\B8VariationsInterface
     */
    public function setFollowSimpleSkuPriceSetting($followSimpleSkuPriceSetting);
}
