<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Plugin\Cms;

use Magento\Cms\Block\Widget\Block;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

/**
 * Class BlockPlugin
 * 
 * Plugin for CMS Block Widget to implement custom caching
 */
class BlockPlugin
{
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * BlockPlugin constructor
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
     * @param Block $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundToHtml(Block $subject, \Closure $proceed): string
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
                'Widget cache hit for CMS block',
                ['cache_key' => $cacheKey, 'block_id' => $subject->getData('block_id')]
            );
            return $cachedContent;
        }

        // Generate content
        $content = $proceed();
        
        // Ensure content is a string (handle null case)
        $content = $content ?? '';
        
        // Cache the content
        $tags = [
            'cms_block',
            'widget_cache'
        ];
        
        $this->widgetCacheHelper->saveWidgetContentToCache($cacheKey, $content, $tags);
        
        $this->widgetCacheHelper->logDebug(
            'Widget cache saved for CMS block',
            ['cache_key' => $cacheKey, 'block_id' => $subject->getData('block_id'), 'content_length' => strlen($content)]
        );

        return $content;
    }

    /**
     * Around getCacheKeyInfo method to enhance cache key generation
     *
     * @param Block $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetCacheKeyInfo(Block $subject, \Closure $proceed): array
    {
        $cacheKeyInfo = $proceed();
     
        // Only modify cache key if widget cache is enabled
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            return $cacheKeyInfo;
        }
        
        // Add widget-specific cache key info
        $cacheKeyInfo[] = 'widget_cache_enabled';
        $cacheKeyInfo[] = $subject->getData('block_id');
        $cacheKeyInfo[] = $subject->getData('template');
        
        return $cacheKeyInfo;
    }
}
