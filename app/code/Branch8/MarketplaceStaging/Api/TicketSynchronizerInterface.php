<?php

namespace Branch8\MarketplaceStaging\Api;

/**
 * Interface for ticket-based product stock synchronization providers.
 */
interface TicketSynchronizerInterface
{
    /**
     * Synchronize stock levels for the given ticket product.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function sync(\Magento\Catalog\Model\Product $product): bool;

    /**
     * Count available serial numbers for a specific batch code or total.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string|null $batchCode
     * @return int
     */
    public function getAvailableCount(\Magento\Catalog\Model\Product $product, ?string $batchCode = null): int;
}
