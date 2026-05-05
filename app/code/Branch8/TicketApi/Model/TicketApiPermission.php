<?php

namespace Branch8\TicketApi\Model;

use Branch8\TicketApi\Api\Data\TicketApiPermissionInterface as ModelInterface;
use Magento\Framework\Model\AbstractModel;

class TicketApiPermission extends AbstractModel implements ModelInterface
{
    const TABLE_NAME    = "ticket_api_permission";
    const ID_FIELD_NAME = "entity_id";

    const ENTITY_ID   = "entity_id";
    const BRAND_ID    = "brand_id";
    const MERCHANT_ID = "merchant_id";
    const MEMO        = "memo";
    const CREATED_AT  = "created_at";
    const UPDATED_AT  = "updated_at";

    protected function _construct()
    {
        $this->_init(
            \Branch8\TicketApi\Model\ResourceModel\TicketApiPermission::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::ENTITY_ID);
    }

    public function getBrandId(): int
    {
        return $this->getData(self::BRAND_ID);
    }

    public function setBrandId(int $brandId)
    {
        $this->setData(self::BRAND_ID, $brandId);
    }

    public function getMerchantId(): int
    {
        return $this->getData(self::MERCHANT_ID);
    }

    public function setMerchantId(int $merchantId)
    {
        $this->setData(self::MERCHANT_ID, $merchantId);
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
}
