<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Plugin\Inventory;

use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\WidgetCache\Helper\Log as ModuleLog;

/**
 * Class SourceItemsSavePlugin
 * 
 * Plugin to clear widget cache when inventory source items are saved
 */
class SourceItemsSavePlugin
{
    private const LOG_OPTION = 'SourceItemsSavePlugin';
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ModuleLog
     */
    private ModuleLog $moduleLog;

    /**
     * SourceItemsSavePlugin constructor
     *
     * @param WidgetCacheHelper $widgetCacheHelper
     * @param ProductRepositoryInterface $productRepository
     * @param ModuleLog $moduleLog Widget cache module logger helper.
     */
    public function __construct(
        WidgetCacheHelper $widgetCacheHelper,
        ProductRepositoryInterface $productRepository,
        ModuleLog $moduleLog
    ) {
        $this->widgetCacheHelper = $widgetCacheHelper;
        $this->productRepository = $productRepository;
        $this->moduleLog = $moduleLog;
    }

    /**
     * Clear widget cache after source items are saved
     *
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function afterExecute(
        SourceItemsSaveInterface $subject,
        $result,
        array $sourceItems
    ) {
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            return;
        }

        if (empty($sourceItems)) {
            return;
        }

        try {
            $productIds = [];
            $skus = [];

            // Extract unique SKUs from source items
            foreach ($sourceItems as $sourceItem) {
                $sku = $sourceItem->getSku();
                if ($sku && !in_array($sku, $skus)) {
                    $skus[] = $sku;
                }
            }

            // Get product IDs from SKUs
            foreach ($skus as $sku) {
                try {
                    $product = $this->productRepository->get($sku);
                    if ($product && $product->getId()) {
                        $productIds[] = $product->getId();
                    }
                } catch (NoSuchEntityException $e) {
                    $this->moduleLog->exception(
                        $e,
                        self::LOG_OPTION,
                        __METHOD__,
                        ['sku' => $sku]
                    );
                    // Product not found, skip
                    continue;
                }
            }

            if (empty($productIds)) {
                return;
            }

            // Generate cache tags for affected products
            // Include both specific product tags and general tags to ensure all widgets are cleared
            $tags = [
                'catalog_product',      // General product tag
                'catalog_product_list' // Product list widget tag
            ];
            foreach ($productIds as $productId) {
                $tags[] = 'catalog_product_' . $productId;
            }

            // Clear widget cache for affected products
            $this->widgetCacheHelper->clearWidgetCache($tags);

            $this->widgetCacheHelper->logDebug(
                'Widget cache cleared after inventory update',
                [
                    'skus' => $skus,
                    'product_ids' => $productIds,
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
    }
}

