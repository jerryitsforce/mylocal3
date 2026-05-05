<?php

namespace Branch8\GeneralNotifyTicket\Api\Data;

use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;

interface GeneralNotifyTicketBatchSettingInterface extends BatchImportTicketBatchSettingModelInterface
{
    public function getId(): ?int;
    public function getSellerId(): int;
    public function setSellerId(int $sellerId);
    public function getBelongToProductId(): int;
    public function setBelongToProductId(int $belongToProductId);
    public function getBatchCode(): string;
    public function setBatchCode(string $batchCode);
    public function getSafetyStock(): ?int;
    public function setSafetyStock(?int $safetyStock);
    public function getSaleStartTime(): string;
    public function setSaleStartTime(string $saleStartTime);
    public function getSaleEndTime(): string;
    public function setSaleEndTime(string $saleEndTime);
    public function getUseStartTime(): ?string;
    public function setUseStartTime(?string $useStartTime);
    public function getUseEndTime(): ?string;
    public function setUseEndTime(?string $useEndTime);
    public function getDueDays(): ?int;
    public function setDueDays(?int $dueDays);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
