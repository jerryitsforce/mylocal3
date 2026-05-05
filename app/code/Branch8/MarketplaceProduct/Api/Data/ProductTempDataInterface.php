<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api\Data;

interface ProductTempDataInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const ID = 'temp_id';
    public const SELLER_ID = 'seller_id';
    public const PRODUCT_ID = 'product_id';
    public const STATUS = 'status';
    public const THUMBNAIL = 'thumbnail';
    public const NAME = 'name';
    public const TYPE = 'type';
    public const SKU = 'sku';
    public const QUANTITY = 'quantity';
    public const COST = 'cost';
    public const PRICE = 'price';
    public const SPECIAL_PRICE = 'special_price';
    public const INFORMATION = 'information';
    public const CREATED_AT = 'created_at';
    /**#@-*/

    /**
     * Returns ID.
     *
     * @return int|null
     */
    public function getId(): ?int;


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
     * Returns seller ID.
     *
     * @return int|null
     */
    public function getSellerId(): ?int;

    /**
     * Sets the seller ID.
     *
     * @param int $sellerId
     *
     * @return $this
     */
    public function setSellerId(int $sellerId): self;

    /**
     * Returns the status,
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Sets the status.
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status): self;

    /**
     * Returns the status.
     *
     * @return string|null
     */
    public function getThumbnail(): ?string;

    /**
     * Sets the thumbnail.
     *
     * @param string $thumbnail
     *
     * @return $this
     */
    public function setThumbnail(string $thumbnail): self;

    /**
     * Returns Name.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Sets Name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Returns Type.
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Sets Type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Returns SKU.
     *
     * @return string|null
     */
    public function getSku(): ?string;

    /**
     * Sets SKU.
     *
     * @param string $sku
     *
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Returns quantity.
     *
     * @return float|null
     */
    public function getQuantity(): ?float;

    /**
     * Sets the cost.
     *
     * @param float $quantity
     *
     * @return $this
     */
    public function setQuantity(float $quantity): self;

    /**
     * Returns cost,
     *
     * @return float|null
     */
    public function getCost(): ?float;

    /**
     * Sets the cost.
     *
     * @param float $cost
     *
     * @return $this
     */
    public function setCost(float $cost): self;

    /**
     * Returns price.
     *
     * @return float|null
     */
    public function getPrice(): ?float;

    /**
     * Sets the price.
     *
     * @param float $price
     *
     * @return self
     */
    public function setPrice(float $price): self;

    /**
     * Returns special price.
     *
     * @return float|null
     */
    public function getSpecialPrice(): ?float;

    /**
     * Sets the special price.
     *
     * @param float $specialPrice
     *
     * @return self
     */
    public function setSpecialPrice(float $specialPrice): self;

    /**
     * Returns information.
     *
     * @return string
     */
    public function getInformation(): string;

    /**
     * Sets the information.
     *
     * @param string $information
     *
     * @return $this
     */
    public function setInformation(string $information): self;

    /**
     * Returns the created at.
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Sets the created at.
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
}

