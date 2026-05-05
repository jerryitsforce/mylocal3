<?php

namespace Branch8\CustomerTicketTable\Api\Data;

interface CustomerTicketInterface
{
    public function getId(): ?int;
    public function getType(): ?int;
    public function setType(int $type);
    public function getTicketTableName(): string;
    public function setTicketTableName(string $ticketTableName);
    public function getTicketTableRecordId(): int;
    public function setTicketTableRecordId(int $ticketTableRecordId);
    public function getBatchCode(): ?string;
    public function setBatchCode(string $batchCode);
    public function getCustomerId(): int;
    public function setCustomerId(int $customerId);
    public function getSalesOrderItemId(): null|int;
    public function setSalesOrderItemId(int $salesOrderItemId);
    public function getBelongToProductId(): int;
    public function setBelongToProductId(int $belongToProductId);
    public function getSellerId(): int;
    public function setSellerId(int $sellerId);
    public function getTicketUniqueContent(): string;
    public function setTicketUniqueContent(string $ticketUniqueContent);
    public function getUseStartTime(): ?string;
    public function setUseStartTime(string $useStartTime);
    public function getUseEndTime(): ?string;
    public function setUseEndTime(string $useEndTime);
    public function getStatus(): int;
    public function setStatus(int $status);
    public function getMemo(): ?string;
    public function setMemo(?string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
