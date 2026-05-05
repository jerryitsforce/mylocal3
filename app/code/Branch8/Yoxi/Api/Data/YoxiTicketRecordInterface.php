<?php

namespace Branch8\Yoxi\Api\Data;

interface YoxiTicketRecordInterface
{
    public function getId(): ?int;
    public function getBatchSettingId(): int;
    public function setBatchSettingId(int $batchSettingId);
    public function getSerialNumber(): string;
    public function setSerialNumber(string $serialNumber);
    public function getSellerId(): int;
    public function setSellerId(int $sellerId);
    public function getBelongToProductId(): int;
    public function setBelongToProductId(int $belongToProductId);
    public function getQuoteItemId(): ?int;
    public function setQuoteItemId(int|null $quoteItemId);
    public function getSalesOrderItemId(): ?int;
    public function setSalesOrderItemId(int|null $salesOrderItemId);
    public function getUseStartTime(): ?string;
    public function setUseStartTime(?string $useStartTime);
    public function getUseEndTime(): ?string;
    public function setUseEndTime(?string $useEndTime);
    public function getDueDays(): ?int;
    public function setDueDays(?int $dueDays);
    public function getUsedDate(): ?string;
    public function setUsedDate(null|string $usedDate);
    public function getUsedTransactionNo(): ?string;
    public function setUsedTransactionNo(null|string $usedTransactionNo);
    public function getUsedStoreNo(): ?string;
    public function setUsedStoreNo(null|string $usedStoreNo);
    public function getUsedCount(): int;
    public function setUsedCount(int $usedCount);
    public function getReturnedDate(): ?string;
    public function setReturnedDate(string $returnedDate);
    public function getStatus(): int;
    public function setStatus(int $status);
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
