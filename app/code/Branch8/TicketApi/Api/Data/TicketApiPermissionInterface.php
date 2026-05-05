<?php

namespace Branch8\TicketApi\Api\Data;

interface TicketApiPermissionInterface
{
    public function getId(): ?int;
    public function getBrandId(): int;
    public function setBrandId(int $brandId);
    public function getMerchantId(): int;
    public function setMerchantId(int $merchantId);
    public function getMemo(): ?string;
    public function setMemo(?string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
