<?php
declare(strict_types=1);

namespace HotaiConnected\ManualInvoice\Api\Data;

interface ManualInvoiceInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const INVOICE_NUMBER = 'invoice_number';
    public const INVOICE_CREATED_AT = 'invoice_created_at';
    public const RANDOM_CODE = 'random_code';
    public const INVOICE_TYPE = 'invoice_type';
    public const INVOICE_STATUS = 'invoice_status';
    public const TRANS_NO = 'trans_no';
    public const COMPANY_NAME = 'company_name';
    public const INVOICE_CUSTOMER_IDENTIFIER = 'invoice_customer_identifier';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const UPDATED_BY = 'updated_by';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Set entity ID
     *
     * @param mixed $entityId
     * @return $this
     */
    public function setEntityId($entityId): self;

    /**
     * Get order ID
     *
     * @return int
     */
    public function getOrderId(): int;

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * Get invoice number
     *
     * @return string|null
     */
    public function getInvoiceNumber(): ?string;

    /**
     * Set invoice number
     *
     * @param string|null $invoiceNumber
     * @return $this
     */
    public function setInvoiceNumber(?string $invoiceNumber): self;

    /**
     * Get invoice created at
     *
     * @return string|null
     */
    public function getInvoiceCreatedAt(): ?string;

    /**
     * Set invoice created at
     *
     * @param string|null $invoiceCreatedAt
     * @return $this
     */
    public function setInvoiceCreatedAt(?string $invoiceCreatedAt): self;

    /**
     * Get random code
     *
     * @return int|null
     */
    public function getRandomCode(): ?int;

    /**
     * Set random code
     *
     * @param int|null $randomCode
     * @return $this
     */
    public function setRandomCode(?int $randomCode): self;

    /**
     * Get invoice type
     *
     * @return string
     */
    public function getInvoiceType(): string;

    /**
     * Set invoice type
     *
     * @param string $invoiceType
     * @return $this
     */
    public function setInvoiceType(string $invoiceType): self;

    /**
     * Get invoice status
     *
     * @return int
     */
    public function getInvoiceStatus(): int;

    /**
     * Set invoice status
     *
     * @param int $invoiceStatus
     * @return $this
     */
    public function setInvoiceStatus(int $invoiceStatus): self;

    /**
     * Get transaction number
     *
     * @return string|null
     */
    public function getTransNo(): ?string;

    /**
     * Set transaction number
     *
     * @param string|null $transNo
     * @return $this
     */
    public function setTransNo(?string $transNo): self;

    /**
     * Get company name
     *
     * @return string|null
     */
    public function getCompanyName(): ?string;

    /**
     * Set company name
     *
     * @param string|null $companyName
     * @return $this
     */
    public function setCompanyName(?string $companyName): self;

    /**
     * Get invoice customer identifier
     *
     * @return string|null
     */
    public function getInvoiceCustomerIdentifier(): ?string;

    /**
     * Set invoice customer identifier
     *
     * @param string|null $invoiceCustomerIdentifier
     * @return $this
     */
    public function setInvoiceCustomerIdentifier(?string $invoiceCustomerIdentifier): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Get updated by
     *
     * @return int
     */
    public function getUpdatedBy(): int;

    /**
     * Set updated by
     *
     * @param int $updatedBy
     * @return $this
     */
    public function setUpdatedBy(int $updatedBy): self;
}