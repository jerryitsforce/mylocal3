<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface;
use Magento\Framework\Model\AbstractModel;

class ProductInitialInformation extends AbstractModel implements ProductInitialInformationInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(ResourceModel\ProductInitialInformation::class);
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
}
