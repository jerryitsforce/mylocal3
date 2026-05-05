<?php

namespace HotaiConnected\OpenHub\Api\Data;

interface OpenHubTicketRecordInterface
{
    /**
     * Get record ID
     *
     * @return int|null
     */
    public function getRecordId(): ?int;

    /**
     * Get sales order item ID
     *
     * @return int
     */
    public function getSalesOrderItemId(): int;

    /**
     * Set sales order item ID
     *
     * @param int $salesOrderItemId
     * @return $this
     */
    public function setSalesOrderItemId(int $salesOrderItemId): OpenHubTicketRecordInterface;

    /**
     * Get belong to product ID
     *
     * @return int
     */
    public function getBelongToProductId(): int;

    /**
     * Set belong to product ID
     *
     * @param int $belongToProductId
     * @return $this
     */
    public function setBelongToProductId(int $belongToProductId): OpenHubTicketRecordInterface;

    /**
     * Get serial number
     *
     * @return string
     */
    public function getSerialNumber(): string;

    /**
     * Set serial number
     *
     * @param string $serialNumber
     * @return $this
     */
    public function setSerialNumber(string $serialNumber): OpenHubTicketRecordInterface;

    /**
     * Get OpenHub order number
     *
     * @return string|null
     */
    public function getOpenHubOrderNo(): ?string;

    /**
     * Set OpenHub order number
     *
     * @param string|null $openHubOrderNo
     * @return $this
     */
    public function setOpenHubOrderNo(?string $openHubOrderNo): OpenHubTicketRecordInterface;

    /**
     * Get transaction number
     *
     * @return string|null
     */
    public function getTransactionNo(): ?string;

    /**
     * Set transaction number
     *
     * @param string|null $transactionNo
     * @return $this
     */
    public function setTransactionNo(?string $transactionNo): OpenHubTicketRecordInterface;

    /**
     * Get app ID
     *
     * @return string|null
     */
    public function getAppId(): ?string;

    /**
     * Set app ID
     *
     * @param string|null $appId
     * @return $this
     */
    public function setAppId(?string $appId): OpenHubTicketRecordInterface;

    /**
     * Get amount
     *
     * @return float|null
     */
    public function getAmount(): ?float;

    /**
     * Set amount
     *
     * @param float|null $amount
     * @return $this
     */
    public function setAmount(?float $amount): OpenHubTicketRecordInterface;

    /**
     * Get OpenHub created at
     *
     * @return string|null
     */
    public function getOpenHubCreatedAt(): ?string;

    /**
     * Set OpenHub created at
     *
     * @param string|null $openHubCreatedAt
     * @return $this
     */
    public function setOpenHubCreatedAt(?string $openHubCreatedAt): OpenHubTicketRecordInterface;

    /**
     * Get remark
     *
     * @return string|null
     */
    public function getRemark(): ?string;

    /**
     * Set remark
     *
     * @param string|null $remark
     * @return $this
     */
    public function setRemark(?string $remark): OpenHubTicketRecordInterface;

    /**
     * Get memo
     *
     * @return string|null
     */
    public function getMemo(): ?string;

    /**
     * Set memo
     *
     * @param string|null $memo
     * @return $this
     */
    public function setMemo(?string $memo): OpenHubTicketRecordInterface;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): OpenHubTicketRecordInterface;
}