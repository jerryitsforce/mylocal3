<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Api\Data;

interface B8VisibleInterface
{
    const TITLE = 'title';
    const IS_VISIBLE = 'is_visible';
    const PRODUCT_ITEM_ID = 'product_item_id';

    /**
     * Get title
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     * @param string $title
     * @return \Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterface
     */
    public function setTitle($title);

    /**
     * Get is_visible
     * @return string|null
     */
    public function getIsVisible();

    /**
     * Set is_visible
     * @param string $isVisible
     * @return \Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterface
     */
    public function setIsVisible($isVisible);

    /**
     * Get product_item_id
     * @return string|null
     */
    public function getProductItemId();

    /**
     * Set product_item_id
     * @param string $productItemId
     * @return \Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterface
     */
    public function setProductItemId($productItemId);
}
