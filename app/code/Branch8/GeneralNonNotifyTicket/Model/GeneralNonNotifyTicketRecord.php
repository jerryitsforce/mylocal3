<?php

namespace Branch8\GeneralNonNotifyTicket\Model;

use Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordInterface;
use Magento\Framework\Model\AbstractModel;

class GeneralNonNotifyTicketRecord extends AbstractModel implements GeneralNonNotifyTicketRecordInterface
{
    const TABLE_NAME    = "general_non_notify_ticket_record";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID            = "record_id";
    const BATCH_SETTING_ID     = "batch_setting_id";
    const SERIAL_NUMBER        = "serial_number";
    const SELLER_ID            = "seller_id";
    const BELONG_TO_PRODUCT_ID = "belong_to_product_id";
    const QUOTE_ITEM_ID        = "quote_item_id";
    const SALES_ORDER_ITEM_ID  = "sales_order_item_id";
    const USE_START_TIME       = "use_start_time";
    const USE_END_TIME         = "use_end_time";
    const DUE_DAYS             = "due_days";
    const USED_DATE            = "used_date";
    const USED_TRANSACTION_NO  = "used_transaction_no";
    const USED_STORE_NO        = "used_store_no";
    const USED_COUNT           = "used_count";
    const RETURNED_DATE        = "returned_date";
    const STATUS               = "status";
    const MEMO                 = "memo";
    const CREATED_AT           = "created_at";
    const UPDATED_AT           = "updated_at";

    // 票券紀錄狀態
    const STATUS_RETURNED  = -1; // 已退貨
    const STATUS_IMPORTED  = 0; // 初始匯入
    const STATUS_ALLOCATED = 1; // 預分配(已指定給quote_item)
    const STATUS_SOLD      = 2; // 已購買(已指定給sales_order_item)
    const STATUS_USED      = 3; // 已使用

    protected function _construct()
    {
        $this->_init(
            \Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getBatchSettingId(): int
    {
        return $this->getData(self::BATCH_SETTING_ID);
    }

    public function setBatchSettingId(int $batchSettingId)
    {
        $this->setData(self::BATCH_SETTING_ID, $batchSettingId);
    }

    public function getSerialNumber(): string
    {
        return $this->getData(self::SERIAL_NUMBER);
    }

    public function setSerialNumber(string $serialNumber)
    {
        $this->setData(self::SERIAL_NUMBER, $serialNumber);
    }

    public function getSellerId(): int
    {
        return $this->getData(self::SELLER_ID);
    }

    public function setSellerId(int $sellerId)
    {
        $this->setData(self::SELLER_ID, $sellerId);
    }

    public function getBelongToProductId(): int
    {
        return $this->getData(self::BELONG_TO_PRODUCT_ID);
    }

    public function setBelongToProductId(int $belongToProductId)
    {
        $this->setData(self::BELONG_TO_PRODUCT_ID, $belongToProductId);
    }

    public function getQuoteItemId(): ?int
    {
        return $this->getData(self::QUOTE_ITEM_ID);
    }

    public function setQuoteItemId(int|null $quoteItemId)
    {
        $this->setData(self::QUOTE_ITEM_ID, $quoteItemId);
    }

    public function getSalesOrderItemId(): ?int
    {
        return $this->getData(self::SALES_ORDER_ITEM_ID);
    }

    public function setSalesOrderItemId(int|null $salesOrderItemId)
    {
        $this->setData(self::SALES_ORDER_ITEM_ID, $salesOrderItemId);
    }

    public function getUseStartTime(): ?string
    {
        return $this->getData(self::USE_START_TIME);
    }

    public function setUseStartTime(?string $useStartTime)
    {
        $this->setData(self::USE_START_TIME, $useStartTime);
    }

    public function getUseEndTime(): ?string
    {
        return $this->getData(self::USE_END_TIME);
    }

    public function setUseEndTime(?string $useEndTime)
    {
        $this->setData(self::USE_END_TIME, $useEndTime);
    }

    public function getDueDays(): ?int
    {
        return $this->getData(self::DUE_DAYS);
    }

    public function setDueDays(?int $dueDays)
    {
        $this->setData(self::DUE_DAYS, $dueDays);
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

    public function getUsedStoreNo(): ?string
    {
        return $this->getData(self::USED_STORE_NO);
    }

    public function setUsedStoreNo(null|string $usedStoreNo)
    {
        $this->setData(self::USED_STORE_NO, $usedStoreNo);
    }

    public function getUsedCount(): int
    {
        return $this->getData(self::USED_COUNT);
    }

    public function setUsedCount(int $usedCount)
    {
        $this->setData(self::USED_COUNT, $usedCount);
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
