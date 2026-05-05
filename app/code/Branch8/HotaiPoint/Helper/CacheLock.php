<?php

namespace Branch8\HotaiPoint\Helper;

use Magento\Framework\App\CacheInterface;

class CacheLock
{
    public const POINT_COMMIT_PROCEDURE_CACHE_KEY       = 'point_commit_procedure_';
    public const POINT_RE_DEDUCTION_PROCEDURE_CACHE_KEY = 'point_re_deduction_procedure_';
    public const CACHE_LIFETIME                         = 60 * 1; // 1 min

    protected $_cache;

    public function __construct(
        CacheInterface $cache
    ) {
        $this->_cache = $cache;
    }

    public function pointProcedureLockByCustomKey(string $customKey): void
    {
        $this->_cache->save(1, $customKey, [], self::CACHE_LIFETIME);
    }

    public function getPointCommitProcedureLockKey(int|string $orderId): string
    {
        return self::POINT_COMMIT_PROCEDURE_CACHE_KEY . $orderId;
    }

    public function pointCommitProcedureLock(int|string $orderId, string $cacheValue = null): void
    {
        if ($cacheValue != null) {
            $this->_cache->save($cacheValue, $this->getPointCommitProcedureLockKey($orderId), [], self::CACHE_LIFETIME);
            return;
        }

        $this->_cache->save(1, $this->getPointCommitProcedureLockKey($orderId), [], self::CACHE_LIFETIME);
    }

    public function pointCommitProcedureUnlock(int|string $orderId): void
    {
        $this->_cache->remove($this->getPointCommitProcedureLockKey($orderId));
    }

    public function checkIsPointCommitProcedureLockNow(int|string $orderId): bool
    {
        return !empty($this->_cache->load($this->getPointCommitProcedureLockKey($orderId)));
    }

    public function getPointCommitProcedureLockValue(int|string $orderId): null|string
    {
        return $this->_cache->load($this->getPointCommitProcedureLockKey($orderId));
    }

    // ---------------------
    public function getPointReDeductionProcedureLockKey(int|string $orderId): string
    {
        return self::POINT_RE_DEDUCTION_PROCEDURE_CACHE_KEY . $orderId;
    }

    public function pointReDeductionProcedureLock(int|string $orderId, string $cacheValue = null): void
    {
        if ($cacheValue != null) {
            $this->_cache->save($cacheValue, $this->getPointReDeductionProcedureLockKey($orderId), [], self::CACHE_LIFETIME);
            return;
        }

        $this->_cache->save(1, $this->getPointReDeductionProcedureLockKey($orderId), [], self::CACHE_LIFETIME);
    }

    public function pointReDeductionProcedureUnlock(int|string $orderId): void
    {
        $this->_cache->remove($this->getPointReDeductionProcedureLockKey($orderId));
    }

    public function checkIsPointReDeductionProcedureLockNow(int|string $orderId): bool
    {
        return !empty($this->_cache->load($this->getPointReDeductionProcedureLockKey($orderId)));
    }

    public function getPointReDeductionProcedureLockValue(int|string $orderId): null|string
    {
        return $this->_cache->load($this->getPointReDeductionProcedureLockKey($orderId));
    }
}
