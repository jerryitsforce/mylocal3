<?php

namespace Branch8\WebkulMpsplitorder\Helper;

use Magento\Framework\App\CacheInterface;

class CacheLock
{
    public const SPLIT_ORDER_PROCEDURE_CACHE_KEY = 'split_order_procedure_';
    public const CACHE_LIFETIME = 60 * 1; // 1 min

    protected $_cache;

    public function __construct(
        CacheInterface $cache
    ) {
        $this->_cache = $cache;
    }

    public function getSplitOrderProcedureLockKey(int|string $masterQuoteId): string
    {
        return self::SPLIT_ORDER_PROCEDURE_CACHE_KEY . $masterQuoteId;
    }

    public function splitOrderProcedureLock(int|string $masterQuoteId, ?string $cacheValue = null): void
    {
        if ($cacheValue != null) {
            $this->_cache->save($cacheValue, $this->getSplitOrderProcedureLockKey($masterQuoteId), [], self::CACHE_LIFETIME);
            return;
        }

        $this->_cache->save(1, $this->getSplitOrderProcedureLockKey($masterQuoteId), [], self::CACHE_LIFETIME);
    }

    public function splitOrderProcedureUnlock(int|string $masterQuoteId): void
    {
        $this->_cache->remove($this->getSplitOrderProcedureLockKey($masterQuoteId));
    }

    public function checkIsSplitOrderProcedureLockNow(int|string $masterQuoteId): bool
    {
        return !empty($this->_cache->load($this->getSplitOrderProcedureLockKey($masterQuoteId)));
    }

    public function getSplitOrderProcedureLockValue(int|string $masterQuoteId): null|string
    {
        return $this->_cache->load($this->getSplitOrderProcedureLockKey($masterQuoteId));
    }
}
