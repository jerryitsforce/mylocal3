<?php
/**
 * Copyright © Branch8 Ltd. All rights reserved.
 * 
 * Plugin to optimize Amasty Groupcat Product Indexer performance in schedule mode.
 * 
 * Problem: The original applyRule() method loads each product individually using
 * ProductRepository::getById(), which causes N+1 query problem. With 91 pending
 * changes and 3 active rules, this results in 273 product loads.
 * 
 * Solution: Use SQL-based approach (getSatisfiedIds) instead of loading products
 * individually. This reduces queries from N to 1 per rule per store.
 */

declare(strict_types=1);

namespace Branch8\Performance\Plugin\Amasty\Groupcat;

use Amasty\Groupcat\Model\Indexer\Product\IndexBuilder;
use Amasty\Groupcat\Model\Rule;
use Psr\Log\LoggerInterface;

class OptimizeIndexBuilder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Around plugin for reindexByProductIds to use optimized approach
     * 
     * Instead of calling applyRule() which loads each product individually,
     * we use the same SQL-based approach as full reindex.
     * 
     * @param IndexBuilder $subject
     * @param callable $proceed
     * @param array $ids
     * @return void
     * @throws \ReflectionException
     */
    public function aroundReindexByProductIds(
        IndexBuilder $subject,
        callable $proceed,
        array $ids
    ): void {
        try {
            // Use reflection to access protected methods
            $reflection = new \ReflectionClass($subject);
            
            // Get active rules
            $getActiveRulesMethod = $reflection->getMethod('getActiveRules');
            $getActiveRulesMethod->setAccessible(true);
            $activeRules = $getActiveRulesMethod->invoke($subject);
            
            foreach ($activeRules as $rule) {
                $this->applyRuleOptimized($subject, $rule, $ids, $reflection);
            }
        } catch (\Exception $e) {
            // If optimization fails, fall back to original method
            $this->logger->warning(
                'Amasty Groupcat Indexer optimization failed, falling back to original method: ' . $e->getMessage()
            );
            $proceed($ids);
        }
    }

    /**
     * Optimized version of applyRule that uses SQL queries instead of loading products
     * 
     * @param IndexBuilder $subject
     * @param Rule $rule
     * @param array $productEntityIds
     * @param \ReflectionClass $reflection
     * @return void
     */
    private function applyRuleOptimized(
        IndexBuilder $subject,
        Rule $rule,
        array $productEntityIds,
        \ReflectionClass $reflection
    ): void {
        // Get resolveStoreIds method
        $resolveStoreIdsMethod = $reflection->getMethod('resolveStoreIds');
        $resolveStoreIdsMethod->setAccessible(true);
        $storeIds = $resolveStoreIdsMethod->invoke($subject, $rule);
        
        if (empty($storeIds)) {
            return;
        }
        
        $productIds = [];
        
        // Get rule conditions
        $conditions = $rule->getConditions();
        
        foreach ($storeIds as $storeId) {
            $params = [
                'store_id' => (int)$storeId
            ];
            
            // Add website_id if rule is not for all websites
            if ($rule->getStoreIds() != [0]) {
                $params['website_id'] = $rule->getWebsiteIds();
            }
            
            try {
                // Use SQL-based approach to get all matching product IDs
                $allMatchingIds = $conditions->getSatisfiedIds($params);
                
                // Filter to only include products we're reindexing
                // This is the key optimization: we only query what we need
                $matchingIdsForReindex = array_intersect($allMatchingIds, $productEntityIds);
                
                foreach ($matchingIdsForReindex as $productId) {
                    $productIds[(int)$productId][(int)$storeId] = true;
                }
                
                $this->logger->debug(sprintf(
                    'Amasty Groupcat Indexer: Rule %d, Store %d - Found %d matching products out of %d total matches (filtering %d products)',
                    $rule->getId(),
                    $storeId,
                    count($matchingIdsForReindex),
                    count($allMatchingIds),
                    count($productEntityIds)
                ));
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Amasty Groupcat Indexer optimization error for rule %d, store %d: %s',
                    $rule->getId(),
                    $storeId,
                    $e->getMessage()
                ));
            }
        }
        
        if (empty($productIds)) {
            return;
        }
        
        // Insert data using the same method as original
        $insertDataMethod = $reflection->getMethod('insertData');
        $insertDataMethod->setAccessible(true);
        $insertDataMethod->invoke($subject, $rule, $productIds);
    }
}
