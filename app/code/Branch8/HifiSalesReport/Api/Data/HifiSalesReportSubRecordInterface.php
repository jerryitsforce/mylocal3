<?php

namespace Branch8\HifiSalesReport\Api\Data;

interface HifiSalesReportSubRecordInterface
{
    public function getId(): ?int;
    public function getParentId(): int;
    public function setParentId(int $parentId);
    public function getBatchCode(): string;
    public function setBatchCode(string $batchCode);
    public function getInvoiceStatus(): int;
    public function setInvoiceStatus(int $invoiceStatus);
    public function getInvoiceChangeDate(): string;
    public function setInvoiceChangeDate(string $invoiceChangeDate);
    public function getSyncStatus(): int;
    public function setSyncStatus(int $syncStatus);
    public function getSyncResult(): int;
    public function setSyncResult(int $syncResult);
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
