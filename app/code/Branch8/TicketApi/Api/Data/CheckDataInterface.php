<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Api\Data;

interface CheckDataInterface
{
    /**
     * @return null|bool
     */
    public function getIsUsable(): null|bool;

    /**
     * @param bool $isUsable
     * @return self
     */
    public function setIsUsable(bool $isUsable): self;

    /**
     * @return null|string
     */
    public function getSerialNoStatus(): null|string;

    /**
     * @param string $serialNoStatus
     * @return self
     */
    public function setSerialNoStatus(string $serialNoStatus): self;

    /**
     * @return null|string
     */
    public function getProductNo(): null|string;

    /**
     * @param null|string $productNo
     * @return self
     */
    public function setProductNo(null|string $productNo): self;

    /**
     * @return null|string
     */
    public function getProductNameM(): null|string;

    /**
     * @param null|string $productNameM
     * @return self
     */
    public function setProductNameM(null|string $productNameM): self;

    /**
     * @return null|string
     */
    public function getProductNameS(): null|string;

    /**
     * @param null|string $productNameS
     * @return self
     */
    public function setProductNameS(null|string $productNameS): self;

    /**
     * @return null|string
     */
    public function getExpiry(): null|string;

    /**
     * @param null|string $expiry
     * @return self
     */
    public function setExpiry(null|string $expiry): self;

    /**
     * @return null|string
     */
    public function getSellPrice(): null|string;

    /**
     * @param null|string $sellPrice
     * @return self
     */
    public function setSellPrice(null|string $sellPrice): self;

    /**
     * @return null|string
     */
    public function getSellPoint(): null|string;

    /**
     * @param null|string $sellPoint
     * @return self
     */
    public function setSellPoint(null|string $sellPoint): self;

    /**
     * @return null|string
     */
    public function getTotalSellAmount(): null|string;

    /**
     * @param null|string $totalSellAmount
     * @return self
     */
    public function setTotalSellAmount(null|string $totalSellAmount): self;

    /**
     * @return null|string
     */
    public function getProductSellPrice(): null|string;

    /**
     * @param null|string $productSellPrice
     * @return self
     */
    public function setProductSellPrice(null|string $productSellPrice): self;

    /**
     * @return null|string
     */
    public function getOrderAmount(): null|string;

    /**
     * @param null|string $orderAmount
     * @return self
     */
    public function setOrderAmount(null|string $orderAmount): self;
}
