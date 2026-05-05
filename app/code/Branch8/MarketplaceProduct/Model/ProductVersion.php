<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Magento\Framework\Model\AbstractModel;

class ProductVersion extends AbstractModel implements ProductVersionInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(ResourceModel\ProductVersion::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $id = $this->getData(self::ID);
        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): ?int
    {
        $productId = $this->getData(self::PRODUCT_ID);
        return $productId ? (int)$productId : null;
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getProductSku(): ?string
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * @inheritdoc
     */
    public function setProductSku(string $productSku): self
    {
        return $this->setData(self::PRODUCT_SKU, $productSku);
    }
    /**
     * @inheritdoc
     */
    public function getProductName(): ?string
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setProductName(string $productName): self
    {
        return $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): ?float
    {
        return (float)$this->getData(self::PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setPrice(float $price): self
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * @inheritdoc
     */
    public function getSpecialPrice(): ?float
    {
        return (float)$this->getData(self::SPECIAL_PRICE);
    }

    /**
     * @inheritdoc
     */
    public function setSpecialPrice(float $specialPrice): self
    {
        return $this->setData(self::SPECIAL_PRICE, $specialPrice);
    }

    /**
     * @inheritdoc
     */
    public function getCost(): ?float
    {
        $cost = $this->getData(self::COST);
        return $cost ? (float)$cost : null;
    }

    /**
     * @inheritdoc
     */
    public function setCost(float $cost): self
    {
        return $this->setData(self::COST, $cost);
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalInformation(): string
    {
        return (string)$this->getData(self::ADDITIONAL_INFORMATION);
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalInformation(string $additionalInformation): self
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalInformation);
    }

    /**
     * @inheritdoc
     */
    public function getReviewerId(): ?int
    {
        $reviewerId = $this->getData(self::REVIEWER_ID);
        return $reviewerId ? (int)$reviewerId : null;
    }

    /**
     * @inheritdoc
     */
    public function setReviewerId(int $reviewerId): self
    {
        return $this->setData(self::REVIEWER_ID, $reviewerId);
    }

    /**
     * @inheritdoc
     */
    public function getDealerProcessed(): int
    {
        return (int)$this->getData(self::DEALER_PROCESSED);
    }

    /**
     * @inheritdoc
     */
    public function setDealerProcessed(int $dealerProcessed): self
    {
        return $this->setData(self::DEALER_PROCESSED, $dealerProcessed);
    }

    /**
     * @inheritdoc
     */
    public function getUserUpdated(): string
    {
        return (string)$this->getData(self::USER_UPDATED);
    }

    /**
     * @inheritdoc
     */
    public function setUserUpdated(string $userUpdated): self
    {
        return $this->setData(self::USER_UPDATED, $userUpdated);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedFrom(): int
    {
        return (int)$this->getData(self::CREATED_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedFrom(int $createdFrom): self
    {
        return $this->setData(self::CREATED_FROM, $createdFrom);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getScheduleStartTime(): string
    {
        return (string)$this->getData(self::SCHEDULE_START_TIME);
    }

    /**
     * @inheritdoc
     */
    public function setScheduleStartTime(string $scheduleStartTime): self
    {
        return $this->setData(self::SCHEDULE_START_TIME, $scheduleStartTime);
    }

    /**
     * @inheritdoc
     */
    public function getScheduleEndTime(): string
    {
        return (string)$this->getData(self::SCHEDULE_END_TIME);
    }

    /**
     * @inheritdoc
     */
    public function setScheduleEndTime(string $scheduleEndTime): self
    {
        return $this->setData(self::SCHEDULE_END_TIME, $scheduleEndTime);
    }

    /**
     * @inheritdoc
     */
    public function getApprovalFlowStatus(): int
    {
        return (int)$this->getData(self::APPROVAL_FLOW_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setApprovalFlowStatus(int $approvalFlowStatus): self
    {
        return $this->setData(self::APPROVAL_FLOW_STATUS, $approvalFlowStatus);
    }

    /**
     * @inheritdoc
     */
    public function getApprovalLog(): string
    {
        return (string)$this->getData(self::APPROVAL_LOG);
    }

    /**
     * @inheritdoc
     */
    public function setApprovalLog(string $approvalLog): self
    {
        return $this->setData(self::APPROVAL_LOG, $approvalLog);
    }

    /**
     * @inheritdoc
     */
    public function getIsVariationCommissionRateNegative(): int
    {
        return (int)$this->getData(self::IS_VARIATION_COMMISSION_RATE_NEGATIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsVariationCommissionRateNegative(int $isVariationCommissionRateNegative): self
    {
        return $this->setData(self::IS_VARIATION_COMMISSION_RATE_NEGATIVE, $isVariationCommissionRateNegative);
    }
}
