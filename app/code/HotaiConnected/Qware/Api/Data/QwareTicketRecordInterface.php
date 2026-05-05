<?php

namespace HotaiConnected\Qware\Api\Data;

interface QwareTicketRecordInterface
{
    public function getId(): ?int;

    public function getQwareOrderNumber(): string;
    public function setQwareOrderNumber(string $qwareOrderNumber);
    public function getQwareProductGuid(): string;
    public function setQwareProductGuid(string $qwareProductGuid);
    public function getBelongToProductId(): int;
    public function setBelongToProductId(int $belongToProductId);
    public function getSalesOrderItemId(): int;
    public function setSalesOrderItemId(int $salesOrderItemId);
    public function getQwareSn(): string;
    public function setQwareSn(string $qwareSn);
    public function getQwareVendorSn(): ?string;
    public function setQwareVendorSn(?string $qwareVendorSn);
    public function getQwareUrl(): string;
    public function setQwareUrl(string $qwareUrl);
    public function getQwarePwd(): string;
    public function setQwarePwd(string $qwarePwd);
    public function getQwareGenerateDate(): string;
    public function setQwareGenerateDate(string $qwareGenerateDate);
    public function getStatus(): int;
    public function setStatus(int $status);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getUsedTransactionNo(): ?string;
    public function setUsedTransactionNo(null|string $usedTransactionNo);
    public function getNotificationType(): ?int;
    public function setNotificationType(null|int $notificationType);
    public function getQwareType(): ?int;
    public function setQwareType(null|int $qwareType);
    public function getQwareQty(): ?int;
    public function setQwareQty(null|int $qwareQty);
    public function getBranchCode(): ?string;
    public function setBranchCode(null|string $branchCode);
    public function getBranchName(): ?string;
    public function setBranchName(null|string $branchName);
    public function getPosCode(): ?string;
    public function setPosCode(null|string $posCode);
    public function getLastAmt(): ?string;
    public function setLastAmt(null|string $lastAmt);
    public function getUsedDate(): ?string;
    public function setUsedDate(null|string $usedDate);
}