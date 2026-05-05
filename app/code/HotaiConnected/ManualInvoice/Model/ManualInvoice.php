<?php
declare(strict_types=1);

namespace HotaiConnected\ManualInvoice\Model;

use HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterface;
use Magento\Framework\Model\AbstractModel;
use HotaiConnected\ManualInvoice\Model\ResourceModel\ManualInvoice as ManualInvoiceResourceModel;

class ManualInvoice extends AbstractModel implements ManualInvoiceInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ManualInvoiceResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): ?int
    {
        return $this->getData(self::ENTITY_ID) ? (int) $this->getData(self::ENTITY_ID) : null;
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId): ManualInvoiceInterface
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): int
    {
        return (int) $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(int $orderId): ManualInvoiceInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getInvoiceNumber(): ?string
    {
        return $this->getData(self::INVOICE_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setInvoiceNumber(?string $invoiceNumber): ManualInvoiceInterface
    {
        return $this->setData(self::INVOICE_NUMBER, $invoiceNumber);
    }

    /**
     * @inheritdoc
     */
    public function getInvoiceCreatedAt(): ?string
    {
        return $this->getData(self::INVOICE_CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setInvoiceCreatedAt(?string $invoiceCreatedAt): ManualInvoiceInterface
    {
        return $this->setData(self::INVOICE_CREATED_AT, $invoiceCreatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getRandomCode(): ?int
    {
        $data = $this->getData(self::RANDOM_CODE);
        return $data ? (int) $data : null;
    }

    /**
     * @inheritdoc
     */
    public function setRandomCode(?int $randomCode): ManualInvoiceInterface
    {
        return $this->setData(self::RANDOM_CODE, $randomCode);
    }

    /**
     * @inheritdoc
     */
    public function getInvoiceType(): string
    {
        return (string) $this->getData(self::INVOICE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setInvoiceType(string $invoiceType): ManualInvoiceInterface
    {
        return $this->setData(self::INVOICE_TYPE, $invoiceType);
    }

    /**
     * @inheritdoc
     */
    public function getInvoiceStatus(): int
    {
        return (int) $this->getData(self::INVOICE_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setInvoiceStatus(int $invoiceStatus): ManualInvoiceInterface
    {
        return $this->setData(self::INVOICE_STATUS, $invoiceStatus);
    }

    /**
     * @inheritdoc
     */
    public function getTransNo(): ?string
    {
        return $this->getData(self::TRANS_NO);
    }

    /**
     * @inheritdoc
     */
    public function setTransNo(?string $transNo): ManualInvoiceInterface
    {
        return $this->setData(self::TRANS_NO, $transNo);
    }

    /**
     * @inheritdoc
     */
    public function getCompanyName(): ?string
    {
        return $this->getData(self::COMPANY_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setCompanyName(?string $companyName): ManualInvoiceInterface
    {
        return $this->setData(self::COMPANY_NAME, $companyName);
    }

    /**
     * @inheritdoc
     */
    public function getInvoiceCustomerIdentifier(): ?string
    {
        return $this->getData(self::INVOICE_CUSTOMER_IDENTIFIER);
    }

    /**
     * @inheritdoc
     */
    public function setInvoiceCustomerIdentifier(?string $invoiceCustomerIdentifier): ManualInvoiceInterface
    {
        return $this->setData(self::INVOICE_CUSTOMER_IDENTIFIER, $invoiceCustomerIdentifier);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): ManualInvoiceInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): ManualInvoiceInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedBy(): int
    {
        return (int) $this->getData(self::UPDATED_BY);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedBy(int $updatedBy): ManualInvoiceInterface
    {
        return $this->setData(self::UPDATED_BY, $updatedBy);
    }
}