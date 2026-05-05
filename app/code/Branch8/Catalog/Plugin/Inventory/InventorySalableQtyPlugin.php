<?php
namespace Branch8\Catalog\Plugin\Inventory;

use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

class InventorySalableQtyPlugin
{
    private $cache = [];

    public function aroundExecute(
        GetProductSalableQtyInterface $subject,
        \Closure $proceed,
        string $sku,
        int $stockId
    ) {
        $cacheKey = $sku . '_' . $stockId;
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $proceed($sku, $stockId);
        }
        return $this->cache[$cacheKey];
    }
}
