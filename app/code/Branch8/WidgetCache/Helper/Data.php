<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Branch8\WidgetCache\Model\WidgetCache;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Class Data
 * 
 * Helper class for widget cache functionality
 */
class Data extends AbstractHelper
{
    /**
     * Configuration paths
     */
    const XML_PATH_WIDGET_CACHE_ENABLED = 'widget_cache/general/enabled';
    const XML_PATH_WIDGET_CACHE_LIFETIME = 'widget_cache/general/lifetime';
    const XML_PATH_WIDGET_CACHE_DEBUG = 'widget_cache/general/debug';
    private const LOG_OPTION = 'Data';

    /**
     * @var WidgetCache
     */
    private $widgetCache;

    /**
     * @var \Branch8\WidgetCache\Logger\Logger
     */
    private $widgetLogger;
    private \Branch8\WidgetCache\Helper\Log $moduleLog;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param WidgetCache $widgetCache
     * @param \Branch8\WidgetCache\Logger\Logger $widgetLogger
     * @param \Branch8\WidgetCache\Helper\Log $moduleLog
     */
    public function __construct(
        Context $context,
        WidgetCache $widgetCache,
        \Branch8\WidgetCache\Logger\Logger $widgetLogger,
        \Branch8\WidgetCache\Helper\Log $moduleLog
    ) {
        parent::__construct($context);
        $this->widgetCache = $widgetCache;
        $this->widgetLogger = $widgetLogger;
        $this->moduleLog = $moduleLog;
    }

    /**
     * Check if widget cache is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isWidgetCacheEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WIDGET_CACHE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get widget cache lifetime
     *
     * @param int|null $storeId
     * @return int
     */
    public function getWidgetCacheLifetime(?int $storeId = null): int
    {
        $lifetime = $this->scopeConfig->getValue(
            self::XML_PATH_WIDGET_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $lifetime ? (int)$lifetime : 3600; // Default 1 hour
    }

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugModeEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_WIDGET_CACHE_DEBUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Generate cache key for widget block
     *
     * @param BlockInterface $block
     * @param array $additionalData
     * @return string
     */
    public function generateWidgetCacheKey(BlockInterface $block, array $additionalData = []): string
    {
        $widgetType = $this->getWidgetTypeFromBlock($block);
        $widgetData = $this->extractWidgetDataFromBlock($block);

        return $this->widgetCache->generateCacheKey($widgetType, $widgetData, $additionalData);
    }

    /**
     * Get cached widget content
     *
     * @param string $cacheKey
     * @return string|null
     */
    public function getCachedWidgetContent(string $cacheKey): ?string
    {
        if (!$this->isWidgetCacheEnabled()) {
            return null;
        }

        return $this->widgetCache->load($cacheKey);
    }

    /**
     * Save widget content to cache
     *
     * @param string $cacheKey
     * @param string $content
     * @param array $tags
     * @return bool
     */
    public function saveWidgetContentToCache(string $cacheKey, string $content, array $tags = []): bool
    {
        if (!$this->isWidgetCacheEnabled()) {
            return false;
        }

        $lifetime = $this->getWidgetCacheLifetime();
        return $this->widgetCache->save($cacheKey, $content, $tags, $lifetime);
    }

    /**
     * Clear widget cache
     *
     * @param array|null $tags
     * @return bool
     */
    public function clearWidgetCache(?array $tags = null): bool
    {
        if ($tags === null) {
            return $this->widgetCache->clearAll();
        }

        return $this->widgetCache->clearByTags($tags);
    }

    /**
     * Get widget type from block
     *
     * @param BlockInterface $block
     * @return string
     */
    private function getWidgetTypeFromBlock(BlockInterface $block): string
    {
        $className = get_class($block);
        
        // Map block classes to widget types
        $typeMapping = [
            'Magento\CatalogWidget\Block\Product\ProductsList' => 'catalog_product_list',
            'Magento\Cms\Block\Widget\Block' => 'cms_block',
            'Branch8\ProductPoint\Block\Widget\ProductPoint' => 'product_point',
            'Branch8\BestSeller\Block\Widget\BestSellerProduct' => 'best_seller',
            'Branch8\Brand\Block\Widget\BrandList' => 'brand_list',
            'Branch8\CatalogCustom\Block\Widget\CategoryList' => 'category_list'
        ];

        return $typeMapping[$className] ?? 'unknown';
    }

    /**
     * Extract widget data from block
     *
     * @param BlockInterface $block
     * @return array
     */
    private function extractWidgetDataFromBlock(BlockInterface $block): array
    {
        $data = [];
        
        // Get common widget properties
        $properties = [
            'conditions',
            'conditions_encoded',
            'page_size',
            'products_count',
            'block_id',
            'template',
            'display_type',
            'show_pager',
            'cache_lifetime',
            'title',  // Add title as common property (used by multiple widgets)
            'store_id'  // Add store_id as common property (used by multiple widgets)
        ];

        foreach ($properties as $property) {
            if ($block->hasData($property)) {
                $data[$property] = $block->getData($property);
            }
        }

        // Add block-specific data
        if ($block instanceof \Magento\CatalogWidget\Block\Product\ProductsList) {
            $data['category_ids'] = $block->getData('category_ids');
            $data['product_sku'] = $block->getData('product_sku');
            $data['products_per_page'] = $block->getData('products_per_page');
        }

        // Add CMS Block widget specific data
        if ($block instanceof \Magento\Cms\Block\Widget\Block) {
            // store_id and block_id are already in common properties
            // No additional specific data needed
        }

        // Add CategoryList widget specific data
        if ($block instanceof \Branch8\CatalogCustom\Block\Widget\CategoryList) {
            $data['id_path'] = $block->getData('id_path');
            $data['max_product'] = $block->getData('max_product');
            $data['widget_location'] = $block->getData('widget_location');
            $data['mall_name'] = $block->getData('mall_name');
            // Required for AJAX category_products_content: without it, all categories share one cache entry
            $data['category_id'] = $block->getData('category_id');
        }

        // Add ProductPoint widget specific data
        if ($block instanceof \Branch8\ProductPoint\Block\Widget\ProductPoint) {
            $data['isAjax'] = $block->getData('isAjax');
            $data['page_var_name'] = $block->getData('page_var_name');
            // title and store_id are already in common properties
        }

        // Add BestSeller widget specific data
        if ($block instanceof \Branch8\BestSeller\Block\Widget\BestSellerProduct) {
            $data['isAjax'] = $block->getData('isAjax');
            $data['item_list_id'] = $block->getData('item_list_id');
            $data['item_list_name'] = $block->getData('item_list_name');
            $data['promotion_id'] = $block->getData('promotion_id');
            $data['promotion_name'] = $block->getData('promotion_name');
            // title is already in common properties
        }

        // Add BrandList widget specific data
        if ($block instanceof \Branch8\Brand\Block\Widget\BrandList) {
            $data['show_images'] = $block->getData('show_images');
            $data['image_width'] = $block->getData('image_width');
            $data['image_height'] = $block->getData('image_height');
            $data['filter_brand'] = $block->getData('filter_brand');
            $data['category'] = $block->getData('category');
            // title is already in common properties
        }

        return $data;
    }

    /**
     * Log debug information
     *
     * @param string $message
     * @param array $context
     */
    public function logDebug(string $message, array $context = []): void
    {
        if ($this->isDebugModeEnabled()) {
            $this->moduleLog->info(
                ['message' => '[WidgetCache] ' . $message, 'context' => $context],
                self::LOG_OPTION
            );
        }
    }

    /**
     * Extract product IDs from widget block using lazy loading (getAllIds)
     * This method only gets product IDs without loading full product objects
     *
     * @param BlockInterface $block
     * @param int|null $limit Maximum number of product IDs to extract (null = no limit)
     * @return array
     */
    public function extractProductIdsFromBlock(BlockInterface $block, ?int $limit = null): array
    {
        $productIds = [];
        
        try {
            // For ProductsList widget
            if ($block instanceof \Magento\CatalogWidget\Block\Product\ProductsList) {
                $collection = $block->createCollection();
                if ($collection) {
                    // Use getAllIds() for better performance - only gets IDs without loading full objects
                    $ids = $collection->getAllIds();
                    if ($limit !== null && $limit > 0) {
                        $ids = array_slice($ids, 0, $limit);
                    }
                    $productIds = array_merge($productIds, $ids);
                }
            }
            // For BestSeller widget
            elseif ($block instanceof \Branch8\BestSeller\Block\Widget\BestSellerProduct) {
                $collection = $block->createCollection();
                if ($collection) {
                    $ids = $collection->getAllIds();
                    if ($limit !== null && $limit > 0) {
                        $ids = array_slice($ids, 0, $limit);
                    }
                    $productIds = array_merge($productIds, $ids);
                }
            }
            // For ProductPoint widget
            elseif ($block instanceof \Branch8\ProductPoint\Block\Widget\ProductPoint) {
                $collection = $block->createCollection();
                if ($collection) {
                    $ids = $collection->getAllIds();
                    if ($limit !== null && $limit > 0) {
                        $ids = array_slice($ids, 0, $limit);
                    }
                    $productIds = array_merge($productIds, $ids);
                }
            }
            // For BrandList widget
            elseif ($block instanceof \Branch8\Brand\Block\Widget\BrandList) {
                $collection = $block->createCollection();
                if ($collection) {
                    $ids = $collection->getAllIds();
                    if ($limit !== null && $limit > 0) {
                        $ids = array_slice($ids, 0, $limit);
                    }
                    $productIds = array_merge($productIds, $ids);
                }
            }
            // Add other widget types as needed
        } catch (\Exception $e) {
            $this->moduleLog->exception(
                $e,
                self::LOG_OPTION,
                __METHOD__,
                ['block_class' => get_class($block)]
            );
        }
        
        return array_unique($productIds);
    }

    /**
     * Generate product-related cache tags
     *
     * @param array $productIds
     * @return array
     */
    public function generateProductCacheTags(array $productIds): array
    {
        $tags = [];
        
        foreach ($productIds as $productId) {
            $tags[] = 'catalog_product_' . $productId;
        }
        
        return $tags;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStatistics(): array
    {
        return [
                'config_enabled' => $this->isWidgetCacheEnabled(),
                'config_lifetime' => $this->getWidgetCacheLifetime(),
                'config_debug' => $this->isDebugModeEnabled()
            ];
    }
}
