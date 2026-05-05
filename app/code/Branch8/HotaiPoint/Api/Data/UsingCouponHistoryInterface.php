<?php

namespace Branch8\HotaiPoint\Api\Data;

interface UsingCouponHistoryInterface
{
    public function getId(): ?int;
    public function getCustomerId(): int;
    public function setCustomerId(int|string $customerId);
    public function getTransSN(): string;
    public function setTransSN(string $transSN);
    public function getTransAt(): string;
    public function setTransAt(string $transAt);
    public function getCouponNo(): string;
    public function setCouponNo(string $couponNo);
    public function getIsSuccess(): int;
    public function setIsSuccess(int|string $isSuccess);
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getCreatedAt(): ?string;
    public function getUpdatedAt(): ?string;
}
