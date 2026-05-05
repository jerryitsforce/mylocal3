<?php
declare(strict_types=1);

namespace HotaiConnected\ManualInvoice\Api;

use HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ManualInvoiceRepositoryInterface
{
    /**
     * Save manual invoice
     *
     * @param ManualInvoiceInterface $manualInvoice
     * @return ManualInvoiceInterface
     * @throws LocalizedException
     */
    public function save(ManualInvoiceInterface $manualInvoice): ManualInvoiceInterface;

    /**
     * Get manual invoice by ID
     *
     * @param int $entityId
     * @return ManualInvoiceInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): ManualInvoiceInterface;

    /**
     * Get manual invoice by order ID
     *
     * @param int $orderId
     * @return ManualInvoiceInterface|null
     */
    public function getByOrderId(int $orderId): ?ManualInvoiceInterface;

    /**
     * Delete manual invoice
     *
     * @param ManualInvoiceInterface $manualInvoice
     * @return bool
     * @throws LocalizedException
     */
    public function delete(ManualInvoiceInterface $manualInvoice): bool;

    /**
     * Delete manual invoice by ID
     *
     * @param int $entityId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteById(int $entityId): bool;
}