<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Model\Api\Data;

use Branch8\TicketApi\Api\Data\CheckDataInterface;
use Magento\Framework\DataObject;

class CheckData extends DataObject implements CheckDataInterface
{
    const INDEX_IS_USABLE          = "isUsable";
    const INDEX_SERIAL_NO_STATUS   = "serialNoStatus";
    const INDEX_PRODUCT_NO         = "productNo";
    const INDEX_PRODUCT_NAME_M     = "productNameM";
    const INDEX_PRODUCT_NAME_S     = "productNameS";
    const INDEX_EXPIRY             = "expiry";
    const INDEX_SELL_PRICE         = "sellPrice";
    const INDEX_SELL_POINT         = "sellPoint";
    const INDEX_TOTAL_SELL_AMOUNT  = "totalSellAmount";
    const INDEX_PRODUCT_SELL_PRICE = "productSellPrice";
    const INDEX_ORDER_AMOUNT       = "orderAmount";

    protected $_data = [];

    /**
     * @inheritDoc
     */
    public function getIsUsable(): null|bool
    {
        return $this->getData(self::INDEX_IS_USABLE);
    }

    /**
     * @inheritDoc
     */
    public function setIsUsable(bool $isUsable): self
    {
        return $this->setData(self::INDEX_IS_USABLE, $isUsable);
    }

    /**
     * @inheritDoc
     */
    public function getSerialNoStatus(): null|string
    {
        return $this->getData(self::INDEX_SERIAL_NO_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setSerialNoStatus(string $serialNoStatus): self
    {
        return $this->setData(self::INDEX_SERIAL_NO_STATUS, $serialNoStatus);
    }

    /**
     * @inheritDoc
     */
    public function getProductNo(): null|string
    {
        return $this->getData(self::INDEX_PRODUCT_NO);
    }

    /**
     * @inheritDoc
     */
    public function setProductNo(null|string $productNo): self
    {
        return $this->setData(self::INDEX_PRODUCT_NO, $productNo);
    }

    /**
     * @inheritDoc
     */
    public function getProductNameM(): null|string
    {
        return $this->getData(self::INDEX_PRODUCT_NAME_M);
    }

    /**
     * @inheritDoc
     */
    public function setProductNameM(null|string $productNameM): self
    {
        return $this->setData(self::INDEX_PRODUCT_NAME_M, $productNameM);
    }

    /**
     * @inheritDoc
     */
    public function getProductNameS(): null|string
    {
        return $this->getData(self::INDEX_PRODUCT_NAME_S);
    }

    /**
     * @inheritDoc
     */
    public function setProductNameS(null|string $productNameS): self
    {
        return $this->setData(self::INDEX_PRODUCT_NAME_S, $productNameS);
    }

    /**
     * @inheritDoc
     */
    public function getExpiry(): null|string
    {
        return $this->getData(self::INDEX_EXPIRY);
    }

    /**
     * @inheritDoc
     */
    public function setExpiry(null|string $expiry): self
    {
        return $this->setData(self::INDEX_EXPIRY, $expiry);
    }

    /**
     * @inheritDoc
     */
    public function getSellPrice(): null|string
    {
        return $this->getData(self::INDEX_SELL_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setSellPrice(null|string $sellPrice): self
    {
        return $this->setData(self::INDEX_SELL_PRICE, $sellPrice);
    }

    /**
     * @inheritDoc
     */
    public function getSellPoint(): null|string
    {
        return $this->getData(self::INDEX_SELL_POINT);
    }

    /**
     * @inheritDoc
     */
    public function setSellPoint(null|string $sellPoint): self
    {
        return $this->setData(self::INDEX_SELL_POINT, $sellPoint);
    }

    /**
     * @inheritDoc
     */
    public function getTotalSellAmount(): null|string
    {
        return $this->getData(self::INDEX_TOTAL_SELL_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalSellAmount(null|string $totalSellAmount): self
    {
        return $this->setData(self::INDEX_TOTAL_SELL_AMOUNT, $totalSellAmount);
    }

    /**
     * @inheritDoc
     */
    public function getProductSellPrice(): null|string
    {
        return $this->getData(self::INDEX_PRODUCT_SELL_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setProductSellPrice(null|string $productSellPrice): self
    {
        return $this->setData(self::INDEX_PRODUCT_SELL_PRICE, $productSellPrice);
    }

    /**
     * @inheritDoc
     */
    public function getOrderAmount(): null|string
    {
        return $this->getData(self::INDEX_ORDER_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setOrderAmount(null|string $orderAmount): self
    {
        return $this->setData(self::INDEX_ORDER_AMOUNT, $orderAmount);
    }
}
