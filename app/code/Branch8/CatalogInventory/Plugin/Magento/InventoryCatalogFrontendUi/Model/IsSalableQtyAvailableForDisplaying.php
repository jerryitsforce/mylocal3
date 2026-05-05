<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CatalogInventory\Plugin\Magento\InventoryCatalogFrontendUi\Model;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

class IsSalableQtyAvailableForDisplaying
{
    /**
     * @var StockItemConfigurationInterface
     */
    private $stockItemConfig;

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     */
    public function __construct(
        StockItemConfigurationInterface $stockItemConfiguration
    ) {
        $this->stockItemConfig = $stockItemConfiguration;
    }

    public function aroundExecute(
        \Magento\InventoryCatalogFrontendUi\Model\IsSalableQtyAvailableForDisplaying $subject,
        \Closure $proceed,
        float $productSalableQty
    ) {
        return ($this->stockItemConfig->getBackorders() === StockItemConfigurationInterface::BACKORDERS_NO
                || $this->stockItemConfig->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO
                && $this->stockItemConfig->getMinQty() < 0)
            && $productSalableQty < (float) $this->stockItemConfig->getStockThresholdQty()
            && $productSalableQty > 0;
    }
}