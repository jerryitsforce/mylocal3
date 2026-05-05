<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Model\Api\Data;

use Branch8\TicketApi\Api\Data\NotifyDataInterface;
use Magento\Framework\DataObject;

class NotifyData extends DataObject implements NotifyDataInterface
{
    const INDEX_USE_STATUS          = "useStatus";
    const INDEX_USED_TRANSACTION_NO = "usedTransactionNo";
    const INDEX_SERIAL_NO           = "serialNo";
    const INDEX_PRODUCT_NO          = "productNo";
    const INDEX_PRODUCT_NAME_M      = "productNameM";
    const INDEX_PRODUCT_NAME_S      = "productNameS";
    const INDEX_SELL_PRICE          = "sellPrice";
    const INDEX_SELL_POINT          = "sellPoint";
    const INDEX_TOTAL_SELL_AMOUNT   = "totalSellAmount";
    const INDEX_PRODUCT_SELL_PRICE  = "productSellPrice";
    const INDEX_ORDER_AMOUNT        = "orderAmount";

    protected $_data = [];

    /**
     * @inheritDoc
     */
    public function getUseStatus(): null|string
    {
        return $this->getData(self::INDEX_USE_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setUseStatus(string $useStatus): self
    {
        return $this->setData(self::INDEX_USE_STATUS, $useStatus);
    }

    /**
     * @inheritDoc
     */
    public function getUsedTransactionNo(): null|string
    {
        return $this->getData(self::INDEX_USED_TRANSACTION_NO);
    }

    /**
     * @inheritDoc
     */
    public function setUsedTransactionNo(string $usedTransactionNo): self
    {
        return $this->setData(self::INDEX_USED_TRANSACTION_NO, $usedTransactionNo);
    }

    /**
     * @inheritDoc
     */
    public function getSerialNo(): null|string
    {
        return $this->getData(self::INDEX_SERIAL_NO);
    }

    /**
     * @inheritDoc
     */
    public function setSerialNo(string $serialNo): self
    {
        return $this->setData(self::INDEX_SERIAL_NO, $serialNo);
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
    public function setProductNo(string $productNo): self
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
    public function setProductNameM(string $productNameM): self
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
    public function setProductNameS(string $productNameS): self
    {
        return $this->setData(self::INDEX_PRODUCT_NAME_S, $productNameS);
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
    public function setSellPrice(string $sellPrice): self
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
    public function setSellPoint(string $sellPoint): self
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
    public function setTotalSellAmount(string $totalSellAmount): self
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
    public function setProductSellPrice(string $productSellPrice): self
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
    public function setOrderAmount(string $orderAmount): self
    {
        return $this->setData(self::INDEX_ORDER_AMOUNT, $orderAmount);
    }
}
