<?php

namespace Branch8\TicketApi\Model;

use Branch8\TicketApi\Api\Data\TicketApiMerchantInterface as ModelInterface;
use Magento\Framework\Model\AbstractModel;

class TicketApiMerchant extends AbstractModel implements ModelInterface
{
    const TABLE_NAME    = "ticket_api_merchant";
    const ID_FIELD_NAME = "entity_id";

    const ENTITY_ID        = "entity_id";
    const MERCHANT_NAME    = "merchant_name";
    const MERCHANT_ID      = "merchant_id";
    const ALLOW_ALL_SELLER = "allow_all_seller";
    const SELLER_ID        = "seller_id";
    const SELLER_IDS       = "seller_ids";
    const WHITELIST        = "whitelist";
    const AES_KEY          = "aes_key";
    const AES_IV           = "aes_iv";
    const IS_ACTIVE        = "is_active";
    const MEMO             = "memo";
    const CREATED_AT       = "created_at";
    const UPDATED_AT       = "updated_at";

    const IS_ACTIVE_FALSE = 0;
    const IS_ACTIVE_TRUE  = 1;

    protected function _construct()
    {
        $this->_init(
            \Branch8\TicketApi\Model\ResourceModel\TicketApiMerchant::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::ENTITY_ID);
    }

    public function getMerchantName(): string
    {
        return $this->getData(self::MERCHANT_NAME);
    }

    public function setMerchantName(string $merchantName)
    {
        $this->setData(self::MERCHANT_NAME, $merchantName);
    }

    public function getMerchantId(): string
    {
        return $this->getData(self::MERCHANT_ID);
    }

    public function setMerchantId(string $merchantId)
    {
        $this->setData(self::MERCHANT_ID, $merchantId);
    }

    public function getAllowAllSeller(): ?int
    {
        return $this->getData(self::ALLOW_ALL_SELLER);
    }

    public function setAllowAllSeller(?int $allowAllSeller)
    {
        $this->setData(self::ALLOW_ALL_SELLER, $allowAllSeller);
    }

    public function getSellerId(): ?int
    {
        return $this->getData(self::SELLER_ID);
    }

    public function setSellerId(?int $sellerId)
    {
        $this->setData(self::SELLER_ID, $sellerId);
    }

    public function getSellerIds(): ?string
    {
        return $this->getData(self::SELLER_IDS);
    }

    public function setSellerIds(?string $sellerIds)
    {
        $this->setData(self::SELLER_IDS, $sellerIds);
    }

    public function getWhitelist(): string
    {
        return $this->getData(self::WHITELIST);
    }

    public function setWhitelist(string $whitelist)
    {
        $this->setData(self::WHITELIST, $whitelist);
    }

    public function getAesKey(): string
    {
        return $this->getData(self::AES_KEY);
    }

    public function setAesKey(string $aesKey)
    {
        $this->setData(self::AES_KEY, $aesKey);
    }

    public function getAesIv(): string
    {
        return $this->getData(self::AES_IV);
    }

    public function setAesIv(string $aesIv)
    {
        $this->setData(self::AES_IV, $aesIv);
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

    public function getWhitelistArray(): array
    {
        $whitelist = $this->getWhitelist();
        $whitelist = str_replace(" ", "", $whitelist);

        return explode(",", $whitelist);
    }
}
