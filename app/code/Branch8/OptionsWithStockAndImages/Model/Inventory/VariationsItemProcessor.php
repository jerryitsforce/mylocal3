<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Model\Inventory;

use Magento\Catalog\Api\Data\ProductInterface as Product;
use Magento\CatalogInventory\Observer\ParentItemProcessorInterface;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Magento\Framework\App\ResourceConnection;

/**
 * Process parent stock item
 */
class VariationsItemProcessor implements ParentItemProcessorInterface
{
    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /**
     * @param Salable $salable
     * @param ResourceConnection $connection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) Deprecated dependencies
     */
    public function __construct(
        Salable $salable,
        ResourceConnection $resourceConnection,
    ) {
        $this->connection = $resourceConnection->getConnection();
        $this->salable = $salable;
    }

    /**
     * Process parent products
     *
     * @param Product $product
     * @return void
     */
    public function process(Product $product)
    {
        $sku = $product->getSku();
        $qty = $this->salable->getQtyBySku($sku);
        // sync stock for is_synced variations
        $this->salable->syncStockForVariations($sku, $qty);
        $this->salable->syncLivesearchInstock($sku);
        // Sync need to refill status for current product
        $this->salable->syncNeedToRefill([$product->getId()]);
    }
}
