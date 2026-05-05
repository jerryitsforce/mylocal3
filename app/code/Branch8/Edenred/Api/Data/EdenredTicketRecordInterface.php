<?php

namespace Branch8\Edenred\Api\Data;

interface EdenredTicketRecordInterface
{
    public function getId(): ?int;

    public function getEdenredOrderNumber(): string;
    public function setEdenredOrderNumber(string $edenredOrderNumber);
    public function getEdenredProductCode(): string;
    public function setEdenredProductCode(string $edenredProductCode);
    public function getEdenredMerchantCode(): string;
    public function setEdenredMerchantCode(string $edenredMerchantCode);
    public function getEdenredClientOrderNumber(): string;
    public function setEdenredClientOrderNumber(string $edenredClientOrderNumber);
    public function getEdenredVoucherNo(): string;
    public function setEdenredVoucherNo(string $edenredVoucherNo);
    public function getEdenredVoucherGuid(): string;
    public function setEdenredVoucherGuid(string $edenredVoucherGuid);
    public function getEdenredGenerateDate(): string;
    public function setEdenredGenerateDate(string $edenredGenerateDate);
    public function getEdenredExpireStartDate(): string;
    public function setEdenredExpireStartDate(string $edenredExpireStartDate);
    public function getEdenredExpireEndDate(): string;
    public function setEdenredExpireEndDate(string $edenredExpireEndDate);
    public function getEdenredShortUrl(): string;
    public function setEdenredShortUrl(string $edenredShortUrl);
    public function getEdenredShortUrlAuthCode(): string;
    public function setEdenredShortUrlAuthCode(string $edenredShortUrlAuthCode);

    public function getBelongToProductId(): int;
    public function setBelongToProductId(int $belongToProductId);
    public function getSalesOrderItemId(): int;
    public function setSalesOrderItemId(int $salesOrderItemId);
    public function getUsedDate(): ?string;
    public function setUsedDate(null|string $usedDate);
    public function getUsedTransactionNo(): ?string;
    public function setUsedTransactionNo(null|string $usedTransactionNo);
    public function getReturnedDate(): ?string;
    public function setReturnedDate(string $returnedDate);
    public function getStatus(): int;
    public function setStatus(int $status);
    public function getMemo(): ?string;
    public function setMemo(string $memo);
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
}
