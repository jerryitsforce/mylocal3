<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Branch8\WidgetCache\Helper\Log as ModuleLog;

/**
 * Class WidgetCache
 *
 * Custom cache implementation for widget content
 */
class WidgetCache
{
    /**
     * Cache tag for widget cache
     */
    const CACHE_TAG = 'WIDGET_CACHE';

    /**
     * Cache lifetime in seconds (1 hour by default)
     */
    const CACHE_LIFETIME = 3600;

    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'widget_cache_';
    private const LOG_OPTION = 'WidgetCache';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var ModuleLog
     */
    private ModuleLog $moduleLog;

    /**
     * WidgetCache constructor
     *
     * @param CacheInterface $cache
     * @param TypeListInterface $cacheTypeList
     * @param Json $jsonSerializer
     * @param Serialize $serialize
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param HttpContext $httpContext
     * @param ModuleLog $moduleLog Widget cache module logger helper.
     */
    public function __construct(
        CacheInterface $cache,
        TypeListInterface $cacheTypeList,
        Json $jsonSerializer,
        Serialize $serialize,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        HttpContext $httpContext,
        ModuleLog $moduleLog
    ) {
        $this->cache = $cache;
        $this->cacheTypeList = $cacheTypeList;
        $this->jsonSerializer = $jsonSerializer;
        $this->serialize = $serialize;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->moduleLog = $moduleLog;
    }

    /**
     * Generate cache key for widget
     *
     * @param string $widgetType
     * @param array $widgetData
     * @param array $additionalData
     * @return string
     */
    public function generateCacheKey(string $widgetType, array $widgetData = [], array $additionalData = []): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $cacheData = [
            'widget_type' => $widgetType,
            'store_id' => $storeId,
            'customer_group_id' => $customerGroupId,
            'widget_data' => $widgetData,
            'additional_data' => $additionalData
        ];

        $cacheKey = self::CACHE_PREFIX . hash('sha256', $this->jsonSerializer->serialize($cacheData));

        return $cacheKey;
    }

    /**
     * Load cached widget content
     *
     * @param string $cacheKey
     * @return string|null
     */
    public function load(string $cacheKey): ?string
    {
        try {
            $cachedData = $this->cache->load($cacheKey);
            if ($cachedData) {
                $unserializedData = $this->serialize->unserialize($cachedData);
                return $unserializedData['content'] ?? null;
            }
        } catch (\Exception $e) {
            $this->moduleLog->exception($e, self::LOG_OPTION, __METHOD__, ['cache_key' => $cacheKey]);
        }

        return null;
    }

    /**
     * Save widget content to cache
     *
     * @param string $cacheKey
     * @param string $content
     * @param array $tags
     * @param int|null $lifetime
     * @return bool
     */
    public function save(string $cacheKey, string $content, array $tags = [], int $lifetime = null): bool
    {
        try {
            $data = [
                'content' => $content,
                'created_at' => time(),
                'widget_type' => $this->extractWidgetTypeFromKey($cacheKey)
            ];

            $serializedData = $this->serialize->serialize($data);
            $cacheTags = array_merge([self::CACHE_TAG], $tags);
            $lifetime = $lifetime ?? self::CACHE_LIFETIME;

            $this->cache->save($serializedData, $cacheKey, $cacheTags, $lifetime);

            return true;
        } catch (\Exception $e) {
            $this->moduleLog->exception($e, self::LOG_OPTION, __METHOD__, ['cache_key' => $cacheKey]);
            return false;
        }
    }

    /**
     * Remove cached widget content
     *
     * @param string $cacheKey
     * @return bool
     */
    public function remove(string $cacheKey): bool
    {
        try {
            $this->cache->remove($cacheKey);
            return true;
        } catch (\Exception $e) {
            $this->moduleLog->exception($e, self::LOG_OPTION, __METHOD__, ['cache_key' => $cacheKey]);
            return false;
        }
    }

    /**
     * Clear all widget cache
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            // Clear custom cache type
            $this->cacheTypeList->cleanType('branch8_widget_cache');

            // Also clear by tags to ensure all widget cache is cleared
            $this->cache->clean([self::CACHE_TAG, 'widget_cache']);
            
            $this->moduleLog->info('Widget Cache: All widget cache cleared', self::LOG_OPTION);
            return true;
        } catch (\Exception $e) {
            $this->moduleLog->exception($e, self::LOG_OPTION, __METHOD__);
            return false;
        }
    }

    /**
     * Clear cache by tags
     *
     * @param array $tags
     * @return bool
     */
    public function clearByTags(array $tags): bool
    {
        try {
            // Since Magento's CacheInterface::clean() doesn't support tag-based clearing,
            // we clear the entire widget cache type to ensure all related caches are invalidated
            // This ensures that any widget containing the affected products will be regenerated
            $this->cacheTypeList->cleanType('branch8_widget_cache');
            
            $this->moduleLog->info(
                'Widget Cache: Cache cleared for tags: ' . implode(', ', $tags) . 
                ' (cleared entire widget cache type)',
                self::LOG_OPTION
            );
            return true;
        } catch (\Exception $e) {
            $this->moduleLog->exception($e, self::LOG_OPTION, __METHOD__, ['tags' => $tags]);
            return false;
        }
    }


    /**
     * Extract widget type from cache key
     *
     * @param string $cacheKey
     * @return string
     */
    private function extractWidgetTypeFromKey(string $cacheKey): string
    {
        // Extract widget type from cache key if possible
        // This is a simplified implementation
        if (strpos($cacheKey, 'catalog_product_list') !== false) {
            return 'catalog_product_list';
        } elseif (strpos($cacheKey, 'cms_block') !== false) {
            return 'cms_block';
        }

        return 'unknown';
    }
}
