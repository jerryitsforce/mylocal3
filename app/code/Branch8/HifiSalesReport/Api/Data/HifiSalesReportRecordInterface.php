<?php

namespace Branch8\HifiSalesReport\Api\Data;

interface HifiSalesReportRecordInterface
{
    public function getId(): ?int;
    public function getInvoiceChangeStartDate(): string;
    public function setInvoiceChangeStartDate(string $invoiceChangeStartDate);
    public function getInvoiceChangeEndDate(): string;
    public function setInvoiceChangeEndDate(string $invoiceChangeEndDate);
    public function getCollectingMethod(): int;
    public function setCollectingMethod(int $collectingMethod);
    public function getClosingDate(): string;
    public function setClosingDate(string $closingDate);
    public function getTransferStatus(): int;
    public function setTransferStatus(int $transferStatus);
    public function getOriginalMainFileName(): string;
    public function setOriginalMainFileName(string $originalMainFileName);
    public function getModifiedMainFileName(): string;
    public function setModifiedMainFileName(string $modifiedMainFileName);
    public function getOriginalDetailFileName(): string;
    public function setOriginalDetailFileName(string $originalDetailFileName);
    public function getModifiedDetailFileName(): string;
    public function setModifiedDetailFileName(string $modifiedDetailFileName);
    public function getStatus(): int;
    public function setStatus(int $status);
    public function getCreatorAdminId(): int;
    public function setCreatorAdminId(int $creatorAdminId);
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
