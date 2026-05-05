<?php

namespace Branch8\FamilyBonusPin\Model\Ticket;

use Branch8\MarketplaceStaging\Model\Ticket\AbstractSynchronizer;
use Magento\Catalog\Model\Product;

/**
 * Synchronizer for FamilyBonusPin tickets.
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
                'family_bonus_pin_ticket_record_v2', 
                'family_bonus_pin_ticket_batch_setting_v2'
            );
        }

        if ($batchCode === null) {
            return array_sum(self::$_stockCache[$productId]);
        }

        $trimmedBatchCode = trim((string)$batchCode);
        return (int)(self::$_stockCache[$productId][$trimmedBatchCode] ?? 0);
    }
}
