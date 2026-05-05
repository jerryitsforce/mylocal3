<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api\Data;

interface ProductInitialInformationInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const PRODUCT_ID = 'product_id';
    public const PRICE = 'price';
    public const SPECIAL_PRICE = 'special_price';
    public const COST = 'cost';
    /**#@-*/

    /**
     * Returns product ID.
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Sets the product ID.
     *
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Returns initial price.
     *
     * @return float|null
     */
    public function getPrice(): ?float;

    /**
     * Sets the initial price.
     *
     * @param float $price
     *
     * @return self
     */
    public function setPrice(float $price): self;

    /**
     * Returns initial special price.
     *
     * @return float|null
     */
    public function getSpecialPrice(): ?float;

    /**
     * Sets the initial special price.
     *
     * @param float $specialPrice
     *
     * @return self
     */
    public function setSpecialPrice(float $specialPrice): self;

    /**
     * Returns initial cost.
     *
     * @return float|null
     */
    public function getCost(): ?float;

    /**
     * Sets the initial cost.
     *
     * @param float $cost
     *
     * @return $this
     */
    public function setCost(float $cost): self;
}

