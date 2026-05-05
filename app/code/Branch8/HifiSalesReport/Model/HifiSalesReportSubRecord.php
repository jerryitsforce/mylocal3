<?php

namespace Branch8\HifiSalesReport\Model;

use Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordInterface;
use Magento\Framework\Model\AbstractModel;

class HifiSalesReportSubRecord extends AbstractModel implements HifiSalesReportSubRecordInterface
{
    const TABLE_NAME    = "hifi_sales_report_sub_record";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID           = "record_id";
    const PARENT_ID           = "parent_id";
    const BATCH_CODE          = "batch_code";
    const INVOICE_STATUS      = "invoice_status";
    const INVOICE_CHANGE_DATE = "invoice_change_date";
    const SYNC_STATUS         = "sync_status";
    const SYNC_RESULT         = "sync_result";
    const MEMO                = "memo";
    const CREATED_AT          = "created_at";
    const UPDATED_AT          = "updated_at";

    // HiFi訂單報表拋賬紀錄表::發票狀態
    const INVOICE_STATUS_ISSUE      = 1; // 開立
    const INVOICE_STATUS_CANCEL     = 2; // 作廢
    const INVOICE_STATUS_ALLOWANCES = 3; // 折讓
    const INVOICE_STATUS_NO_INVOICE = 4; // 不開發票

    // HiFi訂單報表子紀錄表::HIFI拋轉狀態
    const SYNC_STATUS_YET    = 0; // 未拋轉
    const SYNC_STATUS_SYNCED = 1; // 已拋轉

    // HiFi訂單報表子紀錄表::HIFI拋轉結果
    const SYNC_RESULT_ERROR   = -1; // 異常
    const SYNC_RESULT_DEFAULT = 0; // 初始狀態未拋轉
    const SYNC_RESULT_SUCCESS = 1; // 成功

    protected function _construct()
    {
        $this->_init(
            \Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getParentId(): int
    {
        return $this->getData(self::PARENT_ID);
    }

    public function setParentId(int $parentId)
    {
        $this->setData(self::PARENT_ID, $parentId);
    }

    public function getBatchCode(): string
    {
        return $this->getData(self::BATCH_CODE);
    }

    public function setBatchCode(string $batchCode)
    {
        $this->setData(self::BATCH_CODE, $batchCode);
    }

    public function getInvoiceStatus(): int
    {
        return $this->getData(self::INVOICE_STATUS);
    }

    public function setInvoiceStatus(int $invoiceStatus)
    {
        $this->setData(self::INVOICE_STATUS, $invoiceStatus);
    }

    public function getInvoiceChangeDate(): string
    {
        return $this->getData(self::INVOICE_CHANGE_DATE);
    }

    public function setInvoiceChangeDate(string $invoiceChangeDate)
    {
        $this->setData(self::INVOICE_CHANGE_DATE, $invoiceChangeDate);
    }

    public function getSyncStatus(): int
    {
        return $this->getData(self::SYNC_STATUS);
    }

    public function setSyncStatus(int $syncStatus)
    {
        $this->setData(self::SYNC_STATUS, $syncStatus);
    }

    public function getSyncResult(): int
    {
        return $this->getData(self::SYNC_RESULT);
    }

    public function setSyncResult(int $syncResult)
    {
        $this->setData(self::SYNC_RESULT, $syncResult);
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
