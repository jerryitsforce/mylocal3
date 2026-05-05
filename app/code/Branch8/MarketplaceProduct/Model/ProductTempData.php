<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface;
use Magento\Framework\Model\AbstractModel;

class ProductTempData extends AbstractModel implements ProductTempDataInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(ResourceModel\ProductTempData::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $id = $this->getData(self::ID);
        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): ?int
    {
        $productId = $this->getData(self::PRODUCT_ID);
        return $productId ? (int)$productId : null;
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getSellerId(): ?int
    {
        $sellerId = $this->getData(self::SELLER_ID);
        return $sellerId ? (int)$sellerId : null;
    }

    /**
     * @inheritdoc
     */
    public function setSellerId(int $sellerId): self
    {
        return $this->setData(self::SELLER_ID, $sellerId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getThumbnail(): ?string
    {
        return $this->getData(self::THUMBNAIL);
    }

    /**
     * @inheritdoc
     */
    public function setThumbnail(string $thumbnail): self
    {
        return $this->setData(self::THUMBNAIL, $thumbnail);
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getType(): ?string
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setType(string $type): self
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getSku(): ?string
    {
        return $this->getData(self::SKU);
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku): self
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritdoc
     */
    public function getQuantity(): ?float
    {
        return (float)$this->getData(self::QUANTITY);
    }

    /**
     * @inheritdoc
     */
    public function setQuantity(float $quantity): self
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): ?float
    {
        return (float)$this->getData(self::PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setPrice(float $price): self
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritdoc
     */
    public function getSpecialPrice(): ?float
    {
        return (float)$this->getData(self::SPECIAL_PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setSpecialPrice(float $specialPrice): self
    {
        return $this->setData(self::SPECIAL_PRICE, $specialPrice);
    }

    /**
     * @inheritdoc
     */
    public function getCost(): ?float
    {
        $cost = $this->getData(self::COST);
        return $cost ? (float)$cost : null;
    }

    /**
     * @inheritdoc
     */
    public function setCost(float $cost): self
    {
        return $this->setData(self::COST, $cost);
    }

    /**
     * @inheritdoc
     */
    public function getInformation(): string
    {
        return (string)$this->getData(self::INFORMATION);
    }

    /**
     * @inheritdoc
     */
    public function setInformation(string $information): self
    {
        return $this->setData(self::INFORMATION, $information);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
