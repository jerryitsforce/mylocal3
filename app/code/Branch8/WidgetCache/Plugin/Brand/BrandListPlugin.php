<?php

namespace Branch8\WidgetCache\Plugin\Brand;

use Branch8\Brand\Block\Widget\BrandList;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

class BrandListPlugin
{
	/** @var WidgetCacheHelper */
	private $widgetCacheHelper;

	public function __construct(WidgetCacheHelper $widgetCacheHelper)
	{
		$this->widgetCacheHelper = $widgetCacheHelper;
	}

	public function aroundToHtml(BrandList $subject, \Closure $proceed): string
	{
		if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
			$result = $proceed();
			return $result ?? '';
		}

		$cacheKey = $this->widgetCacheHelper->generateWidgetCacheKey($subject);
		$cached = $this->widgetCacheHelper->getCachedWidgetContent($cacheKey);
		if ($cached !== null) {
			$this->widgetCacheHelper->logDebug('Widget cache hit for brand list widget', ['cache_key' => $cacheKey]);
			return $cached;
		}

		$content = $proceed();
		
		// Ensure content is a string (handle null case)
		$content = $content ?? '';
		
		$this->widgetCacheHelper->saveWidgetContentToCache($cacheKey, $content, ['widget_cache', 'brand_list_widget']);
		return $content;
	}
}
