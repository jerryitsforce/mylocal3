<?php

namespace Branch8\Edenred\Model\Ticket;

use Branch8\MarketplaceStaging\Model\Ticket\AbstractSynchronizer;
use Magento\Catalog\Model\Product;

/**
 * Synchronizer for Edenred tickets.
 */
class Synchronizer extends AbstractSynchronizer
{
    /**
     * @param Product $product
     * @param string|null $batchCode
     * @return int
     */
    public function getAvailableCount(Product $product, ?string $batchCode = null): int
    {
        $productId = $product->getData('entity_id') ?: $product->getId();
        
        if (!isset(self::$_stockCache[$productId])) {
            self::$_stockCache[$productId] = $this->fetchAllBatchCounts(
                $product, 
                'edenred_ticket_record', 
                'edenred_ticket_batch_setting'
            );
        }

        if ($batchCode === null) {
            return array_sum(self::$_stockCache[$productId]);
        }

        $trimmedBatchCode = trim((string)$batchCode);
        return (int)(self::$_stockCache[$productId][$trimmedBatchCode] ?? 0);
    }
}
