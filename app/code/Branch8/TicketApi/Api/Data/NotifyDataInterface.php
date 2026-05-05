<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Api\Data;

interface NotifyDataInterface
{
    /**
     * @return null|string
     */
    public function getUseStatus(): null|string;

    /**
     * @param string $useStatus
     * @return self
     */
    public function setUseStatus(string $useStatus): self;

    /**
     * @return null|string
     */
    public function getUsedTransactionNo(): null|string;

    /**
     * @param string $usedTransactionNo
     * @return self
     */
    public function setUsedTransactionNo(string $usedTransactionNo): self;

    /**
     * @return null|string
     */
    public function getSerialNo(): null|string;

    /**
     * @param string $serialNo
     * @return self
     */
    public function setSerialNo(string $serialNo): self;

    /**
     * @return null|string
     */
    public function getProductNo(): null|string;

    /**
     * @param string $productNo
     * @return self
     */
    public function setProductNo(string $productNo): self;

    /**
     * @return null|string
     */
    public function getProductNameM(): null|string;

    /**
     * @param string $productNameM
     * @return self
     */
    public function setProductNameM(string $productNameM): self;

    /**
     * @return null|string
     */
    public function getProductNameS(): null|string;

    /**
     * @param string $productNameS
     * @return self
     */
    public function setProductNameS(string $productNameS): self;

    /**
     * @return null|string
     */
    public function getSellPrice(): null|string;

    /**
     * @param string $sellPrice
     * @return self
     */
    public function setSellPrice(string $sellPrice): self;

    /**
     * @return null|string
     */
    public function getSellPoint(): null|string;

    /**
     * @param string $sellPoint
     * @return self
     */
    public function setSellPoint(string $sellPoint): self;

    /**
     * @return null|string
     */
    public function getTotalSellAmount(): null|string;

    /**
     * @param string $totalSellAmount
     * @return self
     */
    public function setTotalSellAmount(string $totalSellAmount): self;

    /**
     * @return null|string
     */
    public function getProductSellPrice(): null|string;

    /**
     * @param string $productSellPrice
     * @return self
     */
    public function setProductSellPrice(string $productSellPrice): self;

    /**
     * @return null|string
     */
    public function getOrderAmount(): null|string;

    /**
     * @param string $orderAmount
     * @return self
     */
    public function setOrderAmount(string $orderAmount): self;
}
