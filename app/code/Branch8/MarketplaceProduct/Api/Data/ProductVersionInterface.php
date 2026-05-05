<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api\Data;

interface ProductVersionInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const ID = 'id';
    public const PRODUCT_ID = 'product_id';
    public const PRODUCT_SKU = 'product_sku';
    public const PRODUCT_NAME = 'product_name';
    public const STATUS = 'status';
    public const COST = 'cost';
    public const PRICE = 'price';
    public const SPECIAL_PRICE = 'special_price';
    public const ADDITIONAL_INFORMATION = 'additional_information';
    public const REVIEWER_ID = 'reviewer_id';
    public const DEALER_PROCESSED = 'dealer_processed';
    public const USER_UPDATED = 'user_updated';
    public const CREATED_FROM = 'created_from';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const SCHEDULE_START_TIME = 'schedule_start_time';
    public const SCHEDULE_END_TIME = 'schedule_end_time';
    public const APPROVAL_FLOW_STATUS = 'approval_flow_status';
    public const APPROVAL_LOG = 'approval_log';
    public const IS_VARIATION_COMMISSION_RATE_NEGATIVE = 'is_variation_commission_rate_negative';

    /**#@-*/

    /**
     * Returns ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Returns product ID.
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Sets the product ID.
     *
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId(int $productId): self;
    /**
     * Returns product SKU.
     *
     * @return string|null
     */
    public function getProductSku(): ?string;

    /**
     * Sets the product SKU.
     *
     * @param string $productSku
     *
     * @return $this
     */
    public function setProductSku(string $productSku): self;

    /**
     * Returns product Name.
     *
     * @return string|null
     */
    public function getProductName(): ?string;

    /**
     * Sets the product Name.
     *
     * @param string $productName
     *
     * @return $this
     */
    public function setProductName(string $productName): self;

    /**
     * Returns status,
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Sets the status.
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status): self;

    /**
     * Returns cost,
     *
     * @return float|null
     */
    public function getCost(): ?float;

    /**
     * Sets the cost.
     *
     * @param float $cost
     *
     * @return $this
     */
    public function setCost(float $cost): self;

    /**
     * Returns price.
     *
     * @return float|null
     */
    public function getPrice(): ?float;

    /**
     * Sets the price.
     *
     * @param float $price
     *
     * @return self
     */
    public function setPrice(float $price): self;

    /**
     * Returns special price.
     *
     * @return float|null
     */
    public function getSpecialPrice(): ?float;

    /**
     * Sets the special price.
     *
     * @param float $specialPrice
     *
     * @return self
     */
    public function setSpecialPrice(float $specialPrice): self;

    /**
     * Returns additional information.
     *
     * @return string
     */
    public function getAdditionalInformation(): string;

    /**
     * Sets the additional information.
     *
     * @param string $additionalInformation
     *
     * @return $this
     */
    public function setAdditionalInformation(string $additionalInformation): self;

    /**
     * Returns reviewer ID.
     *
     * @return int|null
     */
    public function getReviewerId(): ?int;

    /**
     * Sets the reviewer ID.
     *
     * @param int $reviewerId
     *
     * @return $this
     */
    public function setReviewerId(int $reviewerId): self;

    /**
     * Returns dealer processed.
     *
     * @return int|null
     */
    public function getDealerProcessed(): ?int;

    /**
     * Sets dealer processed.
     *
     * @param int $dealerProcessed
     *
     * @return $this
     */
    public function setDealerProcessed(int $dealerProcessed): self;

    /**
     * Returns user updated.
     *
     * @return string|null
     */
    public function getUserUpdated(): ?string;

    /**
     * Sets the user updated.
     *
     * @param string $userUpdated
     *
     * @return $this
     */
    public function setUserUpdated(string $userUpdated): self;

    /**
     * Returns created from.
     *
     * @return int|null
     */
    public function getCreatedFrom(): ?int;

    /**
     * Sets the created from.
     *
     * @param int $createdFrom
     *
     * @return $this
     */
    public function setCreatedFrom(int $createdFrom): self;

    /**
     * Returns the created at.
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Sets the created at.
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Returns the updated at
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Sets the updated at.
     *
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Returns the schedule start time
     *
     * @return string
     */
    public function getScheduleStartTime(): string;

    /**
     * Sets the schedule start time.
     *
     * @param string $scheduleStartTime
     *
     * @return $this
     */
    public function setScheduleStartTime(string $scheduleStartTime): self;

    /**
     * Returns the schedule end time
     *
     * @return string
     */
    public function getScheduleEndTime(): string;

    /**
     * Sets the schedule end time.
     *
     * @param string $scheduleEndTime
     *
     * @return $this
     */
    public function setScheduleEndTime(string $scheduleEndTime): self;

    /**
     * Returns the approval flow status
     *
     * @return int
     */
    public function getApprovalFlowStatus(): int;

    /**
     * Sets the approval flow status.
     *
     * @param int $approvalFlowStatus
     *
     * @return $this
     */
    public function setApprovalFlowStatus(int $approvalFlowStatus): self;

    /**
     * Returns the approval log
     *
     * @return string
     */
    public function getApprovalLog(): string;

    /**
     * Sets the approval log.
     *
     * @param string $approvalLog
     *
     * @return $this
     */
    public function setApprovalLog(string $approvalLog): self;

    /**
     * Returns the is variation commission rate negative
     *
     * @return int
     */
    public function getIsVariationCommissionRateNegative(): int;

    /**
     * Sets the is_variation_commission_rate_negative.
     *
     * @param int $isVariationCommissionRateNegative
     *
     * @return $this
     */
    public function setIsVariationCommissionRateNegative(int $isVariationCommissionRateNegative): self;
}

