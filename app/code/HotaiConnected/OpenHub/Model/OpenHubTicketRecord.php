<?php

namespace HotaiConnected\OpenHub\Model;

use Magento\Framework\Model\AbstractModel;
use HotaiConnected\OpenHub\Api\Data\OpenHubTicketRecordInterface;

class OpenHubTicketRecord extends AbstractModel implements OpenHubTicketRecordInterface
{
    const TABLE_NAME = "openhub_ticket_record";
    const ID_FIELD_NAME = "record_id";

    // Field constants
    const RECORD_ID              = 'record_id';
    const SALES_ORDER_ITEM_ID    = 'sales_order_item_id';
    const BELONG_TO_PRODUCT_ID   = 'belong_to_product_id';
    const SERIAL_NUMBER          = 'serial_number';
    const OPENHUB_ORDER_NO       = 'openhub_order_no';
    const TRANSACTION_NO         = 'transaction_no';
    const APP_ID                 = 'app_id';
    const AMOUNT                 = 'amount';
    const OPENHUB_CREATED_AT     = 'openhub_created_at';
    const REMARK                 = 'remark';
    const STATUS                 = 'status';
    const MEMO                   = 'memo';
    const USED_DATE              = 'used_date';
    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord::class);
    }

    /**
     * @return int|null
     */
    public function getRecordId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    /**
     * @return int
     */
    public function getSalesOrderItemId(): int
    {
        return (int)$this->getData(self::SALES_ORDER_ITEM_ID);
    }

    /**
     * @param int $salesOrderItemId
     * @return $this
     */
    public function setSalesOrderItemId(int $salesOrderItemId): OpenHubTicketRecordInterface
    {
        return $this->setData(self::SALES_ORDER_ITEM_ID, $salesOrderItemId);
    }

    /**
     * @return int
     */
    public function getBelongToProductId(): int
    {
        return (int)$this->getData(self::BELONG_TO_PRODUCT_ID);
    }

    /**
     * @param int $belongToProductId
     * @return $this
     */
    public function setBelongToProductId(int $belongToProductId): OpenHubTicketRecordInterface
    {
        return $this->setData(self::BELONG_TO_PRODUCT_ID, $belongToProductId);
    }

    /**
     * @return string
     */
    public function getSerialNumber(): string
    {
        return (string)$this->getData(self::SERIAL_NUMBER);
    }

    /**
     * @param string $serialNumber
     * @return $this
     */
    public function setSerialNumber(string $serialNumber): OpenHubTicketRecordInterface
    {
        return $this->setData(self::SERIAL_NUMBER, $serialNumber);
    }

    /**
     * @return string|null
     */
    public function getOpenHubOrderNo(): ?string
    {
        return $this->getData(self::OPENHUB_ORDER_NO);
    }

    /**
     * @param string|null $openHubOrderNo
     * @return $this
     */
    public function setOpenHubOrderNo(?string $openHubOrderNo): OpenHubTicketRecordInterface
    {
        return $this->setData(self::OPENHUB_ORDER_NO, $openHubOrderNo);
    }

    /**
     * @return string|null
     */
    public function getTransactionNo(): ?string
    {
        return $this->getData(self::TRANSACTION_NO);
    }

    /**
     * @param string|null $transactionNo
     * @return $this
     */
    public function setTransactionNo(?string $transactionNo): OpenHubTicketRecordInterface
    {
        return $this->setData(self::TRANSACTION_NO, $transactionNo);
    }

    /**
     * @return string|null
     */
    public function getAppId(): ?string
    {
        return $this->getData(self::APP_ID);
    }

    /**
     * @param string|null $appId
     * @return $this
     */
    public function setAppId(?string $appId): OpenHubTicketRecordInterface
    {
        return $this->setData(self::APP_ID, $appId);
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->getData(self::AMOUNT) ? (float)$this->getData(self::AMOUNT) : null;
    }

    /**
     * @param float|null $amount
     * @return $this
     */
    public function setAmount(?float $amount): OpenHubTicketRecordInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @return string|null
     */
    public function getOpenHubCreatedAt(): ?string
    {
        return $this->getData(self::OPENHUB_CREATED_AT);
    }

    /**
     * @param string|null $openHubCreatedAt
     * @return $this
     */
    public function setOpenHubCreatedAt(?string $openHubCreatedAt): OpenHubTicketRecordInterface
    {
        return $this->setData(self::OPENHUB_CREATED_AT, $openHubCreatedAt);
    }

    /**
     * @return string|null
     */
    public function getRemark(): ?string
    {
        return $this->getData(self::REMARK);
    }

    /**
     * @param string|null $remark
     * @return $this
     */
    public function setRemark(?string $remark): OpenHubTicketRecordInterface
    {
        return $this->setData(self::REMARK, $remark);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getData(self::STATUS) ?: 'unused';
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): OpenHubTicketRecordInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->getData(self::MEMO);
    }

    /**
     * @param string|null $memo
     * @return $this
     */
    public function setMemo(?string $memo): OpenHubTicketRecordInterface
    {
        return $this->setData(self::MEMO, $memo);
    }

    /**
     * @return string|null
     */
    public function getUsedDate(): ?string
    {
        return $this->getData(self::USED_DATE);
    }

    /**
     * @param string|null $usedDate
     * @return $this
     */
    public function setUsedDate(?string $usedDate): OpenHubTicketRecordInterface
    {
        return $this->setData(self::USED_DATE, $usedDate);
    }
}