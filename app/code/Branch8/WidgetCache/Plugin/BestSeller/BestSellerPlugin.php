<?php

namespace Branch8\WidgetCache\Plugin\BestSeller;

use Branch8\BestSeller\Block\Widget\BestSellerProduct;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

class BestSellerPlugin
{
	/** @var WidgetCacheHelper */
	private $widgetCacheHelper;

	public function __construct(WidgetCacheHelper $widgetCacheHelper)
	{
		$this->widgetCacheHelper = $widgetCacheHelper;
	}

	public function aroundToHtml(BestSellerProduct $subject, \Closure $proceed): string
	{
		if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
			$result = $proceed();
			return $result ?? '';
		}

		$cacheKey = $this->widgetCacheHelper->generateWidgetCacheKey($subject);
		$cached = $this->widgetCacheHelper->getCachedWidgetContent($cacheKey);
		if ($cached !== null) {
			$this->widgetCacheHelper->logDebug('Widget cache hit for best seller widget', ['cache_key' => $cacheKey]);
			return $cached;
		}

		$content = $proceed();
		
		// Ensure content is a string (handle null case)
		$content = $content ?? '';
		
		// Lazy load product IDs after content generation
		$productIds = $this->widgetCacheHelper->extractProductIdsFromBlock($subject, 100);
		
		// Generate cache tags with product IDs
		$tags = ['widget_cache', 'bestseller_widget'];
		if (!empty($productIds)) {
			$productTags = $this->widgetCacheHelper->generateProductCacheTags($productIds);
			$tags = array_merge($tags, $productTags);
		}
		
		$this->widgetCacheHelper->saveWidgetContentToCache($cacheKey, $content, $tags);
		
		$this->widgetCacheHelper->logDebug(
			'Widget cache saved for best seller widget',
			['cache_key' => $cacheKey, 'product_count' => count($productIds)]
		);
		
		return $content;
	}
}
