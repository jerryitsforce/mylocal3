<?php

namespace HotaiConnected\Qware\Model;

use HotaiConnected\Qware\Api\Data\QwareTicketRecordInterface;
use Magento\Framework\Model\AbstractModel;

class QwareTicketRecord extends AbstractModel implements QwareTicketRecordInterface
{
    const TABLE_NAME    = "qware_ticket_record";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID                 = "record_id";
    const QWARE_ORDER_NUMBER        = "qware_order_number";
    const QWARE_PRODUCT_GUID        = "qware_product_guid";
    const BELONG_TO_PRODUCT_ID      = "belong_to_product_id";
    const SALES_ORDER_ITEM_ID       = "sales_order_item_id";
    const QWARE_SN                  = "qware_sn";
    const QWARE_VENDOR_SN           = "qware_vendor_sn";
    const QWARE_URL                 = "qware_url";
    const QWARE_PWD                 = "qware_pwd";
    const QWARE_GENERATE_DATE       = "qware_generate_date";
    const STATUS                    = "status";
    const CREATED_AT                = "created_at";
    const UPDATED_AT                = "updated_at";
    const MEMO                      = "memo";
    const USED_TRANSACTION_NO       = "used_transaction_no";
    const NOTIFICATION_TYPE         = "notification_type";
    const QWARE_TYPE                = "qware_type";
    const QWARE_QTY                 = "qware_qty";
    const BRANCH_CODE               = "branch_code";
    const BRANCH_NAME               = "branch_name";
    const POS_CODE                  = "pos_code";
    const LAST_AMT                  = "last_amt";
    const USED_DATE                 = "used_date";

    // 安源票券紀錄狀態
    const STATUS_RETURNED = -1; // 已退貨
    const STATUS_IMPORTED = 0;  // 初始匯入
    const STATUS_UNUSED   = 2;  // 未使用
    const STATUS_USED     = 3;  // 已使用

    protected function _construct()
    {
        $this->_init(
            \HotaiConnected\Qware\Model\ResourceModel\QwareTicketRecord::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getQwareOrderNumber(): string
    {
        return $this->getData(self::QWARE_ORDER_NUMBER);
    }

    public function setQwareOrderNumber(string $qwareOrderNumber)
    {
        $this->setData(self::QWARE_ORDER_NUMBER, $qwareOrderNumber);
    }

    public function getQwareProductGuid(): string
    {
        return $this->getData(self::QWARE_PRODUCT_GUID);
    }

    public function setQwareProductGuid(string $qwareProductGuid)
    {
        $this->setData(self::QWARE_PRODUCT_GUID, $qwareProductGuid);
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

    public function getQwareSn(): string
    {
        return $this->getData(self::QWARE_SN);
    }

    public function setQwareSn(string $qwareSn)
    {
        $this->setData(self::QWARE_SN, $qwareSn);
    }

    public function getQwareVendorSn(): ?string
    {
        return $this->getData(self::QWARE_VENDOR_SN);
    }

    public function setQwareVendorSn(?string $qwareVendorSn)
    {
        $this->setData(self::QWARE_VENDOR_SN, $qwareVendorSn);
    }

    public function getQwareUrl(): string
    {
        return $this->getData(self::QWARE_URL);
    }

    public function setQwareUrl(string $qwareUrl)
    {
        $this->setData(self::QWARE_URL, $qwareUrl);
    }

    public function getQwarePwd(): string
    {
        return $this->getData(self::QWARE_PWD);
    }

    public function setQwarePwd(string $qwarePwd)
    {
        $this->setData(self::QWARE_PWD, $qwarePwd);
    }

    public function getQwareGenerateDate(): string
    {
        return $this->getData(self::QWARE_GENERATE_DATE);
    }

    public function setQwareGenerateDate(string $qwareGenerateDate)
    {
        $this->setData(self::QWARE_GENERATE_DATE, $qwareGenerateDate);
    }

    public function getStatus(): int
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus(int $status)
    {
        $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function getMemo(): ?string
    {
        return $this->getData(self::MEMO);
    }

    public function setMemo(string $memo)
    {
        $this->setData(self::MEMO, $memo);
    }

    public function getUsedTransactionNo(): ?string
    {
        return $this->getData(self::USED_TRANSACTION_NO);
    }

    public function setUsedTransactionNo(null|string $usedTransactionNo)
    {
        $this->setData(self::USED_TRANSACTION_NO, $usedTransactionNo);
    }

    public function getNotificationType(): ?int
    {
        return $this->getData(self::NOTIFICATION_TYPE);
    }

    public function setNotificationType(null|int $notificationType)
    {
        $this->setData(self::NOTIFICATION_TYPE, $notificationType);
    }

    public function getQwareType(): ?int
    {
        return $this->getData(self::QWARE_TYPE);
    }

    public function setQwareType(null|int $qwareType)
    {
        $this->setData(self::QWARE_TYPE, $qwareType);
    }

    public function getQwareQty(): ?int
    {
        return $this->getData(self::QWARE_QTY);
    }

    public function setQwareQty(null|int $qwareQty)
    {
        $this->setData(self::QWARE_QTY, $qwareQty);
    }

    public function getBranchCode(): ?string
    {
        return $this->getData(self::BRANCH_CODE);
    }

    public function setBranchCode(null|string $branchCode)
    {
        $this->setData(self::BRANCH_CODE, $branchCode);
    }

    public function getBranchName(): ?string
    {
        return $this->getData(self::BRANCH_NAME);
    }

    public function setBranchName(null|string $branchName)
    {
        $this->setData(self::BRANCH_NAME, $branchName);
    }

    public function getPosCode(): ?string
    {
        return $this->getData(self::POS_CODE);
    }

    public function setPosCode(null|string $posCode)
    {
        $this->setData(self::POS_CODE, $posCode);
    }

    public function getLastAmt(): ?string
    {
        return $this->getData(self::LAST_AMT);
    }

    public function setLastAmt(null|string $lastAmt)
    {
        $this->setData(self::LAST_AMT, $lastAmt);
    }

    public function getUsedDate(): ?string
    {
        return $this->getData(self::USED_DATE);
    }

    public function setUsedDate(null|string $usedDate)
    {
        $this->setData(self::USED_DATE, $usedDate);
    }
}