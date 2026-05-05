<?php

namespace Branch8\TicketApi\Api\Data;

interface TicketApiBrandInterface
{
    public function getId(): ?int;
    public function getBrandId(): int;
    public function setBrandId(int $brandId);
    public function getBrandName(): string;
    public function setBrandName(string $brandName);
    public function getBrandCode(): string;
    public function setBrandCode(string $brandCode);
    public function getIsActive(): int;
    public function setIsActive(int $isActive);
    public function getMemo(): ?string;
    public function setMemo(?string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
