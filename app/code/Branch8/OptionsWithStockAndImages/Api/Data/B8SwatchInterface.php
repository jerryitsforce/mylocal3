<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Api\Data;

interface B8SwatchInterface
{
    const ENTITY_ID = 'entity_id';
    const OPTION_ID = 'option_id';
    const TITLE = 'title';
    const IS_SWATCH = 'is_swatch';
    const PRODUCT_ID = 'product_id';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Branch8\OptionsWithStockAndImages\B8Swatch\Api\Data\B8SwatchInterface
     */
    public function setEntityId($entityId);

    /**
     * Get option_id
     * @return string|null
     */
    public function getOptionId();

    /**
     * Set option_id
     * @param string $optionId
     * @return \Branch8\OptionsWithStockAndImages\B8Swatch\Api\Data\B8SwatchInterface
     */
    public function setOptionId($optionId);

    /**
     * Get title
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     * @param string $title
     * @return \Branch8\OptionsWithStockAndImages\B8Swatch\Api\Data\B8SwatchInterface
     */
    public function setTitle($title);

    /**
     * Get is_swatch
     * @return string|null
     */
    public function getIsSwatch();

    /**
     * Set is_swatch
     * @param string $isSwatch
     * @return \Branch8\OptionsWithStockAndImages\B8Swatch\Api\Data\B8SwatchInterface
     */
    public function setIsSwatch($isSwatch);

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return \Branch8\OptionsWithStockAndImages\B8Swatch\Api\Data\B8SwatchInterface
     */
    public function setProductId($productId);
}
