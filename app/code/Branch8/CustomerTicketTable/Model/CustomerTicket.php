<?php

namespace Branch8\CustomerTicketTable\Model;

use Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface;
use Magento\Framework\Model\AbstractModel;

class CustomerTicket extends AbstractModel implements CustomerTicketInterface
{
    const TABLE_NAME    = "customer_ticket";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID              = "record_id";
    const TYPE                   = "type";
    const TICKET_TABLE_NAME      = "ticket_table_name";
    const TICKET_TABLE_RECORD_ID = "ticket_table_record_id";
    const BATCH_CODE             = "batch_code";
    const CUSTOMER_ID            = "customer_id";
    const SALES_ORDER_ITEM_ID    = "sales_order_item_id";
    const BELONG_TO_PRODUCT_ID   = "belong_to_product_id";
    const SELLER_ID              = "seller_id";
    const TICKET_UNIQUE_CONTENT  = "ticket_unique_content";
    const USE_START_TIME         = "use_start_time";
    const USE_END_TIME           = "use_end_time";
    const STATUS                 = "status";
    const MEMO                   = "memo";
    const CREATED_AT             = "created_at";
    const UPDATED_AT             = "updated_at";
    const REDEEMED_AT            = 'redeemed_at';

    protected function _construct()
    {
        $this->_init(
            \Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getType(): null|int
    {
        return $this->getData(self::TYPE);
    }

    public function setType(int $type)
    {
        $this->setData(self::TYPE, $type);
    }

    public function getTicketTableName(): string
    {
        return $this->getData(self::TICKET_TABLE_NAME);
    }

    public function setTicketTableName(string $ticketTableName)
    {
        $this->setData(self::TICKET_TABLE_NAME, $ticketTableName);
    }

    public function getTicketTableRecordId(): int
    {
        return $this->getData(self::TICKET_TABLE_RECORD_ID);
    }

    public function setTicketTableRecordId(int $ticketTableRecordId)
    {
        $this->setData(self::TICKET_TABLE_RECORD_ID, $ticketTableRecordId);
    }

    public function getBatchCode(): ?string
    {
        return $this->getData(self::BATCH_CODE);
    }

    public function setBatchCode(string $batchCode)
    {
        $this->setData(self::BATCH_CODE, $batchCode);
    }

    public function getCustomerId(): int
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId(int $customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getSalesOrderItemId(): null|int
    {
        return $this->getData(self::SALES_ORDER_ITEM_ID);
    }

    public function setSalesOrderItemId(int $salesOrderItemId)
    {
        $this->setData(self::SALES_ORDER_ITEM_ID, $salesOrderItemId);
    }

    public function getBelongToProductId(): int
    {
        return $this->getData(self::BELONG_TO_PRODUCT_ID);
    }

    public function setBelongToProductId(int $belongToProductId)
    {
        $this->setData(self::BELONG_TO_PRODUCT_ID, $belongToProductId);
    }

    public function getSellerId(): int
    {
        return $this->getData(self::SELLER_ID);
    }

    public function setSellerId(int $sellerId)
    {
        $this->setData(self::SELLER_ID, $sellerId);
    }

    public function getTicketUniqueContent(): string
    {
        return $this->getData(self::TICKET_UNIQUE_CONTENT);
    }

    public function setTicketUniqueContent(string $ticketUniqueContent)
    {
        $this->setData(self::TICKET_UNIQUE_CONTENT, $ticketUniqueContent);
    }

    public function getUseStartTime(): ?string
    {
        return $this->getData(self::USE_START_TIME);
    }

    public function setUseStartTime(string $useStartTime)
    {
        $this->setData(self::USE_START_TIME, $useStartTime);
    }

    public function getUseEndTime(): ?string
    {
        return $this->getData(self::USE_END_TIME);
    }

    public function setUseEndTime(string $useEndTime)
    {
        $this->setData(self::USE_END_TIME, $useEndTime);
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

    public function setMemo(?string $memo)
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

    public function getRedeemedAt(): null|string
    {
        return $this->getData(self::REDEEMED_AT);
    }

    public function setRedeemedAt(string $redeemedAt)
    {
        $this->setData(self::REDEEMED_AT, $redeemedAt);
    }
}
