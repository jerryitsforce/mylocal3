<?php

namespace Branch8\WidgetCache\Plugin\ProductPoint;

use Branch8\ProductPoint\Block\Widget\ProductPoint;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

class ProductPointPlugin
{
	/** @var WidgetCacheHelper */
	private $widgetCacheHelper;

	public function __construct(WidgetCacheHelper $widgetCacheHelper)
	{
		$this->widgetCacheHelper = $widgetCacheHelper;
	}

	public function aroundToHtml(ProductPoint $subject, \Closure $proceed): string
	{
		if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
			$result = $proceed();
			return $result ?? '';
		}

		$cacheKey = $this->widgetCacheHelper->generateWidgetCacheKey($subject);
		$cached = $this->widgetCacheHelper->getCachedWidgetContent($cacheKey);
		if ($cached !== null) {
			$this->widgetCacheHelper->logDebug('Widget cache hit for product point widget', ['cache_key' => $cacheKey]);
			return $cached;
		}

		$content = $proceed();
		
		// Ensure content is a string (handle null case)
		$content = $content ?? '';
		
		// Lazy load product IDs after content generation
		$productIds = $this->widgetCacheHelper->extractProductIdsFromBlock($subject, 100);
		
		// Generate cache tags with product IDs
		$tags = ['widget_cache', 'product_point_widget'];
		if (!empty($productIds)) {
			$productTags = $this->widgetCacheHelper->generateProductCacheTags($productIds);
			$tags = array_merge($tags, $productTags);
		}
		
		$this->widgetCacheHelper->saveWidgetContentToCache($cacheKey, $content, $tags);
		
		$this->widgetCacheHelper->logDebug(
			'Widget cache saved for product point widget',
			['cache_key' => $cacheKey, 'product_count' => count($productIds)]
		);
		
		return $content;
	}
}
