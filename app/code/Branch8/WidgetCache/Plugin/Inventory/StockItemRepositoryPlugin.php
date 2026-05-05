<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Plugin\Inventory;

use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;
use Branch8\WidgetCache\Helper\Log as ModuleLog;

/**
 * Class StockItemRepositoryPlugin
 * 
 * Plugin to clear widget cache when stock item is saved (Advanced Inventory)
 */
class StockItemRepositoryPlugin
{
    private const LOG_OPTION = 'StockItemRepositoryPlugin';
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * @var ModuleLog
     */
    private ModuleLog $moduleLog;

    /**
     * StockItemRepositoryPlugin constructor
     *
     * @param WidgetCacheHelper $widgetCacheHelper
     * @param ModuleLog $moduleLog Widget cache module logger helper.
     */
    public function __construct(
        WidgetCacheHelper $widgetCacheHelper,
        ModuleLog $moduleLog
    ) {
        $this->widgetCacheHelper = $widgetCacheHelper;
        $this->moduleLog = $moduleLog;
    }

    /**
     * Clear widget cache after stock item is saved
     *
     * @param StockItemRepositoryInterface $subject
     * @param StockItemInterface $result
     * @param StockItemInterface $stockItem
     * @return StockItemInterface
     */
    public function afterSave(
        StockItemRepositoryInterface $subject,
        StockItemInterface $result,
        StockItemInterface $stockItem
    ): StockItemInterface {
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            return $result;
        }

        try {
            $productId = $stockItem->getProductId();
            
            if (!$productId) {
                return $result;
            }

            // Generate cache tags for affected product
            // Include both specific product tag and general tags to ensure all widgets are cleared
            $tags = [
                'catalog_product_' . $productId,  // Specific product tag
                'catalog_product',                // General product tag
                'catalog_product_list'            // Product list widget tag
            ];

            // Clear widget cache for affected product
            $this->widgetCacheHelper->clearWidgetCache($tags);

            $this->widgetCacheHelper->logDebug(
                'Widget cache cleared after stock item save (Advanced Inventory)',
                [
                    'product_id' => $productId,
                    'qty' => $stockItem->getQty(),
                    'is_in_stock' => $stockItem->getIsInStock(),
                    'tags' => $tags
                ]
            );

        } catch (\Exception $e) {
            $this->moduleLog->exception(
                $e,
                self::LOG_OPTION,
                __METHOD__
            );
        }

        return $result;
    }
}

