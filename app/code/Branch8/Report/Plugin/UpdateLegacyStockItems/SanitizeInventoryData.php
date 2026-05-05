<?php

declare(strict_types=1);

namespace Branch8\Report\Plugin\UpdateLegacyStockItems;

use Magento\InventoryCatalog\Model\ResourceModel\UpdateLegacyStockItems;

class SanitizeInventoryData
{
    /**
     * Log stock status for product.
     *
     * @param UpdateLegacyStockItems $subject
     * @param array $productIds
     * @param array $inventoryData
     *
     * @return array
     */
    public function beforeExecute(UpdateLegacyStockItems $subject, array $productIds, array $inventoryData): array
    {
        unset($inventoryData['admin_user_updated']);
        return [$productIds, $inventoryData];
    }
}
