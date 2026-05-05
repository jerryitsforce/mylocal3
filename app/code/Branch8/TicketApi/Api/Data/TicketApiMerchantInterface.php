<?php

namespace Branch8\TicketApi\Api\Data;

interface TicketApiMerchantInterface
{
    public function getId(): ?int;
    public function getMerchantName(): string;
    public function setMerchantName(string $merchantName);
    public function getMerchantId(): string;
    public function setMerchantId(string $merchantId);
    public function getAllowAllSeller(): ?int;
    public function setAllowAllSeller(?int $allowAllSeller);
    public function getSellerId(): ?int;
    public function setSellerId(?int $sellerId);
    public function getSellerIds(): ?string;
    public function setSellerIds(?string $sellerId);
    public function getWhitelist(): string;
    public function setWhitelist(string $whitelist);
    public function getAesKey(): string;
    public function setAesKey(string $aesKey);
    public function getAesIv(): string;
    public function setAesIv(string $aesIv);
    public function getIsActive(): int;
    public function setIsActive(int $isActive);
    public function getMemo(): ?string;
    public function setMemo(?string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
