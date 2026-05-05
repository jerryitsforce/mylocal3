<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Plugin\CatalogWidget;

use Magento\CatalogWidget\Block\Product\ProductsList;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

/**
 * Class ProductsListPlugin
 * 
 * Plugin for Catalog Product List Widget to implement custom caching
 */
class ProductsListPlugin
{
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * ProductsListPlugin constructor
     *
     * @param WidgetCacheHelper $widgetCacheHelper
     */
    public function __construct(
        WidgetCacheHelper $widgetCacheHelper
    ) {
        $this->widgetCacheHelper = $widgetCacheHelper;
    }

    /**
     * Around toHtml method to implement widget caching
     *
     * @param ProductsList $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundToHtml(ProductsList $subject, \Closure $proceed): string
    {
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            $result = $proceed();
            return $result ?? '';
        }

        $cacheKey = $this->widgetCacheHelper->generateWidgetCacheKey($subject);
        
        // Try to get cached content
        $cachedContent = $this->widgetCacheHelper->getCachedWidgetContent($cacheKey);
        
        if ($cachedContent !== null) {
            $this->widgetCacheHelper->logDebug(
                'Widget cache hit for catalog product list',
                ['cache_key' => $cacheKey]
            );
            return $cachedContent;
        }

        // Generate content
        $content = $proceed();
        
        // Ensure content is a string (handle null case)
        $content = $content ?? '';
        
        // Lazy load product IDs after content generation (only when saving cache)
        // Limit to first 100 products to avoid performance issues with large collections
        $productIds = $this->widgetCacheHelper->extractProductIdsFromBlock($subject, 100);
        
        // Generate cache tags with product IDs
        $tags = [
            'catalog_product',
            'catalog_category',
            'widget_cache',
            'catalog_product_list'
        ];
        
        // Add product-specific tags (lazy loaded)
        if (!empty($productIds)) {
            $productTags = $this->widgetCacheHelper->generateProductCacheTags($productIds);
            $tags = array_merge($tags, $productTags);
        }
        
        $this->widgetCacheHelper->saveWidgetContentToCache($cacheKey, $content, $tags);
        
        $this->widgetCacheHelper->logDebug(
            'Widget cache saved for catalog product list',
            [
                'cache_key' => $cacheKey,
                'content_length' => strlen($content),
                'product_count' => count($productIds)
            ]
        );

        return $content;
    }

    /**
     * Around getCacheKeyInfo method to enhance cache key generation
     *
     * @param ProductsList $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetCacheKeyInfo(ProductsList $subject, \Closure $proceed): array
    {
        $cacheKeyInfo = $proceed();
     
        // Only modify cache key if widget cache is enabled
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            return $cacheKeyInfo;
        }
        
        // Add widget-specific cache key info
        $cacheKeyInfo[] = 'widget_cache_enabled';
        $cacheKeyInfo[] = $subject->getData('conditions_encoded');
        $cacheKeyInfo[] = $subject->getData('page_size');
        $cacheKeyInfo[] = $subject->getData('products_count');
        
        return $cacheKeyInfo;
    }
}
