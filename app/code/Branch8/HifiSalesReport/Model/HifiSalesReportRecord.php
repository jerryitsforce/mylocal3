<?php

namespace Branch8\HifiSalesReport\Model;

use Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordInterface;
use Magento\Framework\Model\AbstractModel;

class HifiSalesReportRecord extends AbstractModel implements HifiSalesReportRecordInterface
{
    const TABLE_NAME    = "hifi_sales_report_record";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID                 = "record_id";
    const INVOICE_CHANGE_START_DATE = "invoice_change_start_date";
    const INVOICE_CHANGE_END_DATE   = "invoice_change_end_date";
    const COLLECTING_METHOD         = "collecting_method";
    const CLOSING_DATE              = "closing_date";
    const TRANSFER_STATUS           = "transfer_status";
    const ORIGINAL_MAIN_FILE_NAME   = "original_main_file_name";
    const MODIFIED_MAIN_FILE_NAME   = "modified_main_file_name";
    const ORIGINAL_DETAIL_FILE_NAME = "original_detail_file_name";
    const MODIFIED_DETAIL_FILE_NAME = "modified_detail_file_name";
    const STATUS                    = "status";
    const CREATOR_ADMIN_ID          = "creator_admin_id";
    const MEMO                      = "memo";
    const CREATED_AT                = "created_at";
    const UPDATED_AT                = "updated_at";

    // HiFi訂單報表拋賬紀錄表::發票狀態
    const INVOICE_STATUS_ISSUE      = 1; // 開立
    const INVOICE_STATUS_CANCEL     = 2; // 作廢
    const INVOICE_STATUS_ALLOWANCES = 3; // 折讓
    const INVOICE_STATUS_NO_INVOICE = 4; // 不開發票

    // HiFi訂單報表拋賬紀錄表::收款方式
    const COLLECTING_METHOD_ALL              = 0; // 全部
    const COLLECTING_METHOD_CREDIT           = 1; // 信用
    const COLLECTING_METHOD_CONVENIENT_STORE = 2; // 超商收款

    // HiFi訂單報表拋賬紀錄表::拋轉狀態
    const TRANSFER_STATUS_YET  = 0; // 尚未拋轉
    const TRANSFER_STATUS_DONE = 1; // 已拋轉

    // HiFi訂單報表拋賬紀錄表::紀錄狀態
    const STATUS_CREATED   = 0; // 初始建立
    const STATUS_CONFIRMED = 1; // 已確認
    const STATUS_SYNCED    = 2; // 已同步到HIFI

    protected function _construct()
    {
        $this->_init(
            \Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getInvoiceChangeStartDate(): string
    {
        return $this->getData(self::INVOICE_CHANGE_START_DATE);
    }

    public function setInvoiceChangeStartDate(string $invoiceChangeStartDate)
    {
        $this->setData(self::INVOICE_CHANGE_START_DATE, $invoiceChangeStartDate);
    }

    public function getInvoiceChangeEndDate(): string
    {
        return $this->getData(self::INVOICE_CHANGE_END_DATE);
    }

    public function setInvoiceChangeEndDate(string $invoiceChangeEndDate)
    {
        $this->setData(self::INVOICE_CHANGE_END_DATE, $invoiceChangeEndDate);
    }

    public function getCollectingMethod(): int
    {
        return $this->getData(self::COLLECTING_METHOD);
    }

    public function setCollectingMethod(int $collectingMethod)
    {
        $this->setData(self::COLLECTING_METHOD, $collectingMethod);
    }

    public function getClosingDate(): string
    {
        return $this->getData(self::CLOSING_DATE);
    }

    public function setClosingDate(string $closingDate)
    {
        $this->setData(self::CLOSING_DATE, $closingDate);
    }

    public function getTransferStatus(): int
    {
        return $this->getData(self::TRANSFER_STATUS);
    }

    public function setTransferStatus(int $transferStatus)
    {
        $this->setData(self::TRANSFER_STATUS, $transferStatus);
    }

    public function getOriginalMainFileName(): string
    {
        return $this->getData(self::ORIGINAL_MAIN_FILE_NAME);
    }

    public function setOriginalMainFileName(string $originalMainFileName)
    {
        $this->setData(self::ORIGINAL_MAIN_FILE_NAME, $originalMainFileName);
    }

    public function getModifiedMainFileName(): string
    {
        return $this->getData(self::MODIFIED_MAIN_FILE_NAME);
    }

    public function setModifiedMainFileName(string $modifiedMainFileName)
    {
        $this->setData(self::MODIFIED_MAIN_FILE_NAME, $modifiedMainFileName);
    }

    public function getOriginalDetailFileName(): string
    {
        return $this->getData(self::ORIGINAL_DETAIL_FILE_NAME);
    }

    public function setOriginalDetailFileName(string $originalDetailFileName)
    {
        $this->setData(self::ORIGINAL_DETAIL_FILE_NAME, $originalDetailFileName);
    }

    public function getModifiedDetailFileName(): string
    {
        return $this->getData(self::MODIFIED_DETAIL_FILE_NAME);
    }

    public function setModifiedDetailFileName(string $modifiedDetailFileName)
    {
        $this->setData(self::MODIFIED_DETAIL_FILE_NAME, $modifiedDetailFileName);
    }

    public function getStatus(): int
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(int $status)
    {
        $this->setData(self::STATUS, $status);
    }

    public function getCreatorAdminId(): int
    {
        return $this->getData(self::CREATOR_ADMIN_ID);
    }

    public function setCreatorAdminId(int $creatorAdminId)
    {
        $this->setData(self::CREATOR_ADMIN_ID, $creatorAdminId);
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
