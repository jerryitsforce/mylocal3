<?php

namespace Branch8\TicketApi\Model;

use Branch8\TicketApi\Api\Data\TicketApiBrandInterface as ModelInterface;
use Magento\Framework\Model\AbstractModel;

class TicketApiBrand extends AbstractModel implements ModelInterface
{
    const TABLE_NAME    = "ticket_api_brand";
    const ID_FIELD_NAME = "entity_id";

    const ENTITY_ID  = "entity_id";
    const BRAND_ID   = "brand_id";
    const BRAND_NAME = "brand_name";
    const BRAND_CODE = "brand_code";
    const IS_ACTIVE  = "is_active";
    const MEMO       = "memo";
    const CREATED_AT = "created_at";
    const UPDATED_AT = "updated_at";

    const IS_ACTIVE_FALSE = 0;
    const IS_ACTIVE_TRUE  = 1;

    protected function _construct()
    {
        $this->_init(
            \Branch8\TicketApi\Model\ResourceModel\TicketApiBrand::class
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

    public function getBrandName(): string
    {
        return $this->getData(self::BRAND_NAME);
    }

    public function setBrandName(string $brandName)
    {
        $this->setData(self::BRAND_NAME, $brandName);
    }

    public function getBrandCode(): string
    {
        return $this->getData(self::BRAND_CODE);
    }

    public function setBrandCode(string $brandCode)
    {
        $this->setData(self::BRAND_CODE, $brandCode);
    }

    public function getIsActive(): int
    {
        return $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(int $isActive)
    {
        $this->setData(self::IS_ACTIVE, $isActive);
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
