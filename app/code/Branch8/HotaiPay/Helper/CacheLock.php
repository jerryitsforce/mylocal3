<?php

namespace Branch8\HotaiPay\Helper;

use Magento\Framework\App\CacheInterface;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;

class CacheLock
{
    public const UPDATE_STATUS_AFTER_CHECKOUT_SUCCESS_CACHE_KEY = 'update_status_after_checkout_success_';
    public const CACHE_LIFETIME = 60 * 1; // 1 min

    protected CacheInterface $_cache;
    private HotaiPayLogHelper $hotaiPayLogHelper;

    public function __construct(
        CacheInterface $cache,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->_cache = $cache;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }

    public function getUpdateStatusAfterCheckoutSuccessLockKey(int|string $parentOrderId): string
    {
        return self::UPDATE_STATUS_AFTER_CHECKOUT_SUCCESS_CACHE_KEY . $parentOrderId;
    }

    public function updateStatusAfterCheckoutSuccessLock(int|string $parentOrderId, null|string $cacheValue = null): void
    {
        if ($cacheValue != null) {
            $this->_cache->save($cacheValue, $this->getUpdateStatusAfterCheckoutSuccessLockKey($parentOrderId), [], self::CACHE_LIFETIME);
            return;
        }

        $this->_cache->save(1, $this->getUpdateStatusAfterCheckoutSuccessLockKey($parentOrderId), [], self::CACHE_LIFETIME);
    }

    public function updateStatusAfterCheckoutSuccessUnlock(int|string $parentOrderId): void
    {
        $this->_cache->remove($this->getUpdateStatusAfterCheckoutSuccessLockKey($parentOrderId));
    }

    public function checkIsUpdateStatusAfterCheckoutSuccessLockNow(int|string $parentOrderId): bool
    {
        return !empty($this->_cache->load($this->getUpdateStatusAfterCheckoutSuccessLockKey($parentOrderId)));
    }

    public function getUpdateStatusAfterCheckoutSuccessLockValue(int|string $parentOrderId): null|string
    {
        return $this->_cache->load($this->getUpdateStatusAfterCheckoutSuccessLockKey($parentOrderId));
    }

    public function writeLog(string $message): void
    {
        $this->hotaiPayLogHelper->writeLog($message, __CLASS__);
    }
}
