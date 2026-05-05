<?php

namespace Branch8\HotaiPoint\Api\Data;

interface TransferPointHistoryInterface
{
    public function getId(): ?int;
    public function getFromCustomerId(): int;
    public function setFromCustomerId(int|string $fromCustomerId);
    public function getFromCustomerOneid(): string;
    public function setFromCustomerOneid(string $fromCustomerOneid);
    public function getFromCustomerMemberAccount(): string;
    public function setFromCustomerMemberAccount(string $fromCustomerMemberAccount);
    public function getToCustomerOneid(): string;
    public function setToCustomerOneid(string $toCustomerOneid);
    public function getToCustomerMemberAccount(): string;
    public function setToCustomerMemberAccount(string $toCustomerMemberAccount);
    public function getTransferPoint(): int;
    public function setTransferPoint(int|string $transferPoint);
    public function getTransSN(): string;
    public function setTransSN(string $transSN);
    public function getTransAt(): string;
    public function setTransAt(string $transAt);
    public function getIsSuccess(): int;
    public function setIsSuccess(int|string $isSuccess);
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getCreatedAt(): ?string;
    public function getUpdatedAt(): ?string;
}
