<?php

namespace Branch8\Edenred\Model;

use Branch8\Edenred\Api\Data\EdenredTicketRecordInterface;
use Magento\Framework\Model\AbstractModel;

class EdenredTicketRecord extends AbstractModel implements EdenredTicketRecordInterface
{
    const TABLE_NAME    = "edenred_ticket_record";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID                   = "record_id";
    const EDENRED_ORDER_NUMBER        = "edenred_order_number";
    const EDENRED_PRODUCT_CODE        = "edenred_product_code";
    const EDENRED_MERCHANT_CODE       = "edenred_merchant_code";
    const EDENRED_CLIENT_ORDER_NUMBER = "edenred_client_order_number";
    const EDENRED_VOUCHER_NO          = "edenred_voucher_no";
    const EDENRED_VOUCHER_GUID        = "edenred_voucher_guid";
    const EDENRED_GENERATE_DATE       = "edenred_generate_date";
    const EDENRED_EXPIRE_START_DATE   = "edenred_expire_start_date";
    const EDENRED_EXPIRE_END_DATE     = "edenred_expire_end_date";
    const EDENRED_SHORT_URL           = "edenred_short_url";
    const EDENRED_SHORT_URL_AUTH_CODE = "edenred_short_url_auth_code";
    const BELONG_TO_PRODUCT_ID        = "belong_to_product_id";
    const SALES_ORDER_ITEM_ID         = "sales_order_item_id";
    const USED_DATE                   = "used_date";
    const USED_TRANSACTION_NO         = "used_transaction_no";
    const RETURNED_DATE               = "returned_date";
    const STATUS                      = "status";
    const MEMO                        = "memo";
    const CREATED_AT                  = "created_at";
    const UPDATED_AT                  = "updated_at";

    // 宜睿票券紀錄狀態
    const STATUS_RETURNED = -1; // 已退貨
    const STATUS_IMPORTED = 0; // 初始匯入
    const STATUS_USED     = 1; // 已使用
    const STATUS_CANCELED = 2; // 已取消

    protected function _construct()
    {
        $this->_init(
            \Branch8\Edenred\Model\ResourceModel\EdenredTicketRecord::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getEdenredOrderNumber(): string
    {
        return $this->getData(self::EDENRED_ORDER_NUMBER);
    }

    public function setEdenredOrderNumber(string $edenredOrderNumber)
    {
        $this->setData(self::EDENRED_ORDER_NUMBER, $edenredOrderNumber);
    }

    public function getEdenredProductCode(): string
    {
        return $this->getData(self::EDENRED_PRODUCT_CODE);
    }

    public function setEdenredProductCode(string $edenredProductCode)
    {
        $this->setData(self::EDENRED_PRODUCT_CODE, $edenredProductCode);
    }

    public function getEdenredMerchantCode(): string
    {
        return $this->getData(self::EDENRED_MERCHANT_CODE);
    }

    public function setEdenredMerchantCode(string $edenredMerchantCode)
    {
        $this->setData(self::EDENRED_MERCHANT_CODE, $edenredMerchantCode);
    }

    public function getEdenredClientOrderNumber(): string
    {
        return $this->getData(self::EDENRED_CLIENT_ORDER_NUMBER);
    }

    public function setEdenredClientOrderNumber(string $edenredClientOrderNumber)
    {
        $this->setData(self::EDENRED_CLIENT_ORDER_NUMBER, $edenredClientOrderNumber);
    }

    public function getEdenredVoucherNo(): string
    {
        return $this->getData(self::EDENRED_VOUCHER_NO);
    }

    public function setEdenredVoucherNo(string $edenredVoucherNo)
    {
        $this->setData(self::EDENRED_VOUCHER_NO, $edenredVoucherNo);
    }

    public function getEdenredVoucherGuid(): string
    {
        return $this->getData(self::EDENRED_VOUCHER_GUID);
    }

    public function setEdenredVoucherGuid(string $edenredVoucherGuid)
    {
        $this->setData(self::EDENRED_VOUCHER_GUID, $edenredVoucherGuid);
    }

    public function getEdenredGenerateDate(): string
    {
        return $this->getData(self::EDENRED_GENERATE_DATE);
    }

    public function setEdenredGenerateDate(string $edenredGenerateDate)
    {
        $this->setData(self::EDENRED_GENERATE_DATE, $edenredGenerateDate);
    }

    public function getEdenredExpireStartDate(): string
    {
        return $this->getData(self::EDENRED_EXPIRE_START_DATE);
    }

    public function setEdenredExpireStartDate(string $edenredExpireStartDate)
    {
        $this->setData(self::EDENRED_EXPIRE_START_DATE, $edenredExpireStartDate);
    }

    public function getEdenredExpireEndDate(): string
    {
        return $this->getData(self::EDENRED_EXPIRE_END_DATE);
    }

    public function setEdenredExpireEndDate(string $edenredExpireEndDate)
    {
        $this->setData(self::EDENRED_EXPIRE_END_DATE, $edenredExpireEndDate);
    }

    public function getEdenredShortUrl(): string
    {
        return $this->getData(self::EDENRED_SHORT_URL);
    }

    public function setEdenredShortUrl(string $edenredShortUrl)
    {
        $this->setData(self::EDENRED_SHORT_URL, $edenredShortUrl);
    }

    public function getEdenredShortUrlAuthCode(): string
    {
        return $this->getData(self::EDENRED_SHORT_URL_AUTH_CODE);
    }

    public function setEdenredShortUrlAuthCode(string $edenredShortUrlAuthCode)
    {
        $this->setData(self::EDENRED_SHORT_URL_AUTH_CODE, $edenredShortUrlAuthCode);
    }

    public function getBelongToProductId(): int
    {
        return $this->getData(self::BELONG_TO_PRODUCT_ID);
    }

    public function setBelongToProductId(int $belongToProductId)
    {
        $this->setData(self::BELONG_TO_PRODUCT_ID, $belongToProductId);
    }

    public function getSalesOrderItemId(): int
    {
        return $this->getData(self::SALES_ORDER_ITEM_ID);
    }

    public function setSalesOrderItemId(int $salesOrderItemId)
    {
        $this->setData(self::SALES_ORDER_ITEM_ID, $salesOrderItemId);
    }

    public function getUsedDate(): ?string
    {
        return $this->getData(self::USED_DATE);
    }

    public function setUsedDate(null|string $usedDate)
    {
        $this->setData(self::USED_DATE, $usedDate);
    }

    public function getUsedTransactionNo(): ?string
    {
        return $this->getData(self::USED_TRANSACTION_NO);
    }

    public function setUsedTransactionNo(null|string $usedTransactionNo)
    {
        $this->setData(self::USED_TRANSACTION_NO, $usedTransactionNo);
    }

    public function getReturnedDate(): ?string
    {
        return $this->getData(self::RETURNED_DATE);
    }

    public function setReturnedDate(string $returnedDate)
    {
        $this->setData(self::RETURNED_DATE, $returnedDate);
    }

    public function getStatus(): int
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(int $status)
    {
        $this->setData(self::STATUS, $status);
    }

    public function getMemo(): ?string
    {
        return $this->getData(self::MEMO);
    }

    public function setMemo(string $memo)
    {
        $this->setData(self::MEMO, $memo);
    }

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
