<?php
/**
 * Copyright © Branch8 Ltd. All rights reserved.
 * 
 * Plugin to optimize Branch8_RestrictedProduct indexer by caching frequently accessed data.
 * 
 * Problem: Every time processUpdateProduct() is called, it:
 * 1. Loads all customer groups (getCustomerGroupsIds)
 * 2. Loads all active hide_product rules (ruleCollectionFactory)
 * 
 * These rarely change but are loaded on every indexer run.
 * 
 * Solution: Cache these values to reduce database queries.
 */

declare(strict_types=1);

namespace Branch8\Performance\Plugin\Branch8\RestrictedProduct;

use Branch8\RestrictedProduct\Helper\Data as RestrictedProductHelper;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CacheRestrictedProductData
{
    /**
     * Cache key for customer groups
     */
    const CACHE_KEY_CUSTOMER_GROUPS = 'branch8_restricted_product_customer_groups';
    
    /**
     * Cache key for active hide_product rule IDs
     */
    const CACHE_KEY_ACTIVE_RULE_IDS = 'branch8_restricted_product_active_rule_ids';
    
    /**
     * Cache lifetime (1 hour)
     */
    const CACHE_LIFETIME = 3600;
    
    /**
     * Cache tag
     */
    const CACHE_TAG = 'branch8_restricted_product';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Cache customer groups to avoid loading on every indexer run
     * 
     * @param RestrictedProductHelper $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetCustomerGroupsIds(
        RestrictedProductHelper $subject,
        callable $proceed
    ): array {
        // Try to get from cache
        $cached = $this->cache->load(self::CACHE_KEY_CUSTOMER_GROUPS);
        
        if ($cached) {
            try {
                $customerGroups = $this->serializer->unserialize($cached);
                $this->logger->debug('Branch8_RestrictedProduct: Loaded customer groups from cache');
                return $customerGroups;
            } catch (\Exception $e) {
                $this->logger->warning('Branch8_RestrictedProduct: Failed to unserialize cached customer groups: ' . $e->getMessage());
            }
        }
        
        // Load from database
        $customerGroups = $proceed();
        
        // Save to cache
        try {
            $this->cache->save(
                $this->serializer->serialize($customerGroups),
                self::CACHE_KEY_CUSTOMER_GROUPS,
                [self::CACHE_TAG],
                self::CACHE_LIFETIME
            );
            $this->logger->debug('Branch8_RestrictedProduct: Saved customer groups to cache');
        } catch (\Exception $e) {
            $this->logger->warning('Branch8_RestrictedProduct: Failed to cache customer groups: ' . $e->getMessage());
        }
        
        return $customerGroups;
    }

    /**
     * Optimize processUpdateProduct by caching rule IDs
     * 
     * Note: We can't easily cache the entire rule collection loading,
     * so we'll just add logging to monitor performance.
     * 
     * @param RestrictedProductHelper $subject
     * @param callable $proceed
     * @param array $productIds
     * @return void
     */
    public function aroundProcessUpdateProduct(
        RestrictedProductHelper $subject,
        callable $proceed,
        array $productIds
    ): void {
        $startTime = microtime(true);
        
        $this->logger->debug(sprintf(
            'Branch8_RestrictedProduct: Starting processUpdateProduct for %d products',
            count($productIds)
        ));
        
        $proceed($productIds);
        
        $duration = microtime(true) - $startTime;
        $this->logger->debug(sprintf(
            'Branch8_RestrictedProduct: Completed processUpdateProduct in %.3f seconds',
            $duration
        ));
    }
}
