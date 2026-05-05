<?php

namespace Branch8\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;
use Amasty\Groupcat\Model\ActiveRuleResolver;

class CustomerLogoutCacheCleanerObserver implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var ActiveRuleResolver
     */
    private $activeRuleResolver;

    /**
     * @param LoggerInterface $logger
     * @param CacheInterface $cache
     * @param ActiveRuleResolver $activeRuleResolver
     */
    public function __construct(
        LoggerInterface $logger,
        CacheInterface $cache,
        ActiveRuleResolver $activeRuleResolver
    ) {
        $this->logger = $logger;
        $this->cache = $cache;
        $this->activeRuleResolver = $activeRuleResolver;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if (!$customer || !$customer->getId()) {
            return;
        }

        $customerId = (int)$customer->getId();

        // Remove validation rules cache using Amasty's cache key generator
        $cacheKey = 'validrules|' . $this->activeRuleResolver->getCacheKeyForActiveRules();
        $cacheRemoved = false;

        // Get cache data before removal using Magento's public API
        $beforeData = $this->cache->load($cacheKey);
        $beforeExists = ($beforeData !== false);

        // Only attempt to remove if cache exists
        if ($beforeExists) {
            try {
                $cacheRemoved = $this->cache->remove($cacheKey);
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                    $this->logger->error('Error removing validation rules cache on logout', [
                        'customer_id' => $customerId,
                        'cache_key' => $cacheKey,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Get cache data after removal using Magento's public API
        $afterData = $this->cache->load($cacheKey);
        $afterExists = ($afterData !== false);

        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
            $this->logger->info('Validation rules cache removed on customer logout', [
                'customer_id' => $customerId,
                'cache_key' => $cacheKey,
                'removed' => $cacheRemoved,
                'before_exists' => $beforeExists,
                'before_data' => $beforeExists ? $beforeData : null,
                'after_exists' => $afterExists,
                'after_data' => $afterExists ? $afterData : null
            ]);
        }
    }
}
