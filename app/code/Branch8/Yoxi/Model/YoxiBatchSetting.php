<?php

namespace Branch8\Yoxi\Model;

use Branch8\Yoxi\Api\Data\YoxiBatchSettingInterface;
use Magento\Framework\Model\AbstractModel;

class YoxiBatchSetting extends AbstractModel implements YoxiBatchSettingInterface
{
    const TABLE_NAME    = "yoxi_batch_setting_v2";
    const ID_FIELD_NAME = "setting_id";

    const SETTING_ID           = "setting_id";
    const SELLER_ID            = "seller_id";
    const BELONG_TO_PRODUCT_ID = "belong_to_product_id";
    const BATCH_CODE           = "batch_code";
    const SAFETY_STOCK         = "safety_stock";
    const SALE_START_TIME      = "sale_start_time";
    const SALE_END_TIME        = "sale_end_time";
    const USE_START_TIME       = "use_start_time";
    const USE_END_TIME         = "use_end_time";
    const DUE_DAYS             = "due_days";
    const CREATED_AT           = "created_at";
    const UPDATED_AT           = "updated_at";

    protected function _construct()
    {
        $this->_init(
            \Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::SETTING_ID);
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

    public function getBatchCode(): string
    {
        return $this->getData(self::BATCH_CODE);
    }

    public function setBatchCode(string $batchCode)
    {
        $this->setData(self::BATCH_CODE, $batchCode);
    }

    public function getSafetyStock(): int
    {
        return $this->getData(self::SAFETY_STOCK);
    }

    public function setSafetyStock(int $safetyStock)
    {
        $this->setData(self::SAFETY_STOCK, $safetyStock);
    }

    public function getSaleStartTime(): string
    {
        return $this->getData(self::SALE_START_TIME);
    }

    public function setSaleStartTime(string $saleStartTime)
    {
        $this->setData(self::SALE_START_TIME, $saleStartTime);
    }

    public function getSaleEndTime(): string
    {
        return $this->getData(self::SALE_END_TIME);
    }

    public function setSaleEndTime(string $saleEndTime)
    {
        $this->setData(self::SALE_END_TIME, $saleEndTime);
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

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
