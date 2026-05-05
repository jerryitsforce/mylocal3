<?php
declare(strict_types=1);

namespace Branch8\HotaiCore\Helper;

use Magento\Framework\App\CacheInterface;

/**
 * Redis Lock Helper
 * Provides distributed locking mechanism using Redis cache
 */
class RedisLock
{
    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * Default lock lifetime in seconds (5 minutes)
     */
    public const DEFAULT_LOCK_LIFETIME = 60 * 5;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check if a lock exists for the given key
     *
     * @param string $lockKey
     * @return bool
     */
    public function isLocked(string $lockKey): bool
    {
        return !empty($this->cache->load($lockKey));
    }

    /**
     * Acquire a lock with the given key
     *
     * @param string $lockKey
     * @param int $lifetime Lock lifetime in seconds (default: 5 minutes)
     * @param mixed $value Lock value (default: 1)
     * @return void
     */
    public function acquire(string $lockKey, int $lifetime = self::DEFAULT_LOCK_LIFETIME, mixed $value = 1): void
    {
        $this->cache->save($value, $lockKey, [], $lifetime);
    }

    /**
     * Release a lock by removing the key
     *
     * @param string $lockKey
     * @return void
     */
    public function release(string $lockKey): void
    {
        $this->cache->remove($lockKey);
    }

    /**
     * Get the lock value if it exists
     *
     * @param string $lockKey
     * @return mixed|null
     */
    public function getLockValue(string $lockKey): mixed
    {
        return $this->cache->load($lockKey);
    }

    /**
     * Generate a lock key with prefix
     *
     * @param string $prefix
     * @param string $identifier
     * @return string
     */
    public function generateLockKey(string $prefix, string $identifier): string
    {
        return $prefix . hash('sha256', $identifier);
    }
}

