<?php
namespace Branch8\HotaiAuth\Plugin\Magento\Framework\Session;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Exception\HotaiAuthException;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Carbon\Carbon;
use JsonException;
use Magento\Customer\Model\Session\Proxy as CustomerSessionProxy;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Lock\LockManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Log_Exception;

/**
 * SessionManager Plugin for Hotai Token Management
 *
 * This plugin handles token refresh with distributed locking to prevent race conditions
 * in multi-server (load balanced) environments.
 *
 * Key features:
 * - Atomic distributed locking using LockManagerInterface (Redis-based)
 * - Centralized token storage in Redis cache for cross-server synchronization
 * - Pre-emptive token refresh before expiry
 */
#[AllowDynamicProperties] class SessionManager
{
    /**
     * Lock TTL in seconds - auto-expire to prevent deadlock
     */
    private const LOCK_TTL = 10;

    /**
     * Maximum wait time in seconds when lock cannot be acquired
     */
    private const MAX_WAIT_TIME = 5;

    /**
     * Pre-emptive refresh buffer in minutes (refresh before actual expiry)
     */
    private const REFRESH_BUFFER_MINUTES = 3;

    /**
     * Lock key prefix for refresh token lock (shared across all servers via Redis)
     */
    private const LOCK_KEY_PREFIX = 'hotai_refresh_lock_';

    /**
     * Token cache key prefix for centralized token storage
     */
    private const TOKEN_CACHE_PREFIX = 'hotai_token_cache_';

    /**
     * Token cache TTL in seconds (30 minutes)
     */
    private const TOKEN_CACHE_TTL = 1800;

    /**
     * Sleep interval in microseconds for waiting (100ms)
     */
    private const WAIT_SLEEP_INTERVAL = 100000;

    private HotaiAuthService $hotaiAuthService;
    private LoggerInterface $loggerInterface;
    private ManagerInterface $eventManager;
    private CacheInterface $cache;
    private MobileDetect $mobileDetect;
    private CustomerSessionProxy $customerSession;
    private LockManagerInterface $lockManager;

    public function __construct(
        HotaiAuthService $hotaiAuthService,
        LoggerInterface $loggerInterface,
        ManagerInterface $eventManager,
        CacheInterface $cache,
        MobileDetect $mobileDetect,
        CustomerSessionProxy $customerSession,
        LockManagerInterface $lockManager
    ) {
        $this->hotaiAuthService = $hotaiAuthService;
        $this->loggerInterface = $loggerInterface;
        $this->eventManager = $eventManager;
        $this->cache = $cache;
        $this->mobileDetect = $mobileDetect;
        $this->customerSession = $customerSession;
        $this->lockManager = $lockManager;
    }

    /**
     * @param \Magento\Framework\Session\SessionManager $session
     * @param callable $proceed
     * @param $method
     * @param $args
     * @return mixed|string
     * @throws JsonException
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function around__call(
        \Magento\Framework\Session\SessionManager $session,
        callable $proceed,
        $method,
        $args
    ) {return $proceed($method, $args);
        if ($method === 'setHotaiToken') {
            $result = $proceed($method, $args);
            $this->saveLatestHotaiToken($session, $args);
            // Also save to centralized cache for cross-server sync
            $this->saveTokenToCache($args[0]);
            return $result;
        }

        if ($method !== 'getHotaiToken' || !isset($session->getData()['hotai_token'])) {
            return $proceed($method, $args);
        }

        $hotaiToken = $session->getData()['hotai_token'];

        if (!isset($hotaiToken['accessToken'])) {
            return '';
        }

        // Check if token needs refresh (with pre-emptive buffer)
        if ($this->isTokenExpiredOrNearExpiry($hotaiToken)) {
            return $this->handleTokenRefresh($session, $hotaiToken);
        }

        return $hotaiToken['accessToken'];
    }

    /**
     * Check if token is expired or near expiry (pre-emptive refresh)
     */
    private function isTokenExpiredOrNearExpiry(array $hotaiToken): bool
    {
        $expiredAt = $hotaiToken['expiredAt'] ?? null;
        if (!$expiredAt) {
            return true;
        }

        $refreshThreshold = Carbon::now()->addMinutes(self::REFRESH_BUFFER_MINUTES);

        // Handle both Carbon instance and string
        if ($expiredAt instanceof Carbon) {
            return $refreshThreshold->gte($expiredAt);
        }

        try {
            $expiredAtCarbon = Carbon::parse($expiredAt);
            return $refreshThreshold->gte($expiredAtCarbon);
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Handle token refresh with distributed locking mechanism to prevent race condition
     * Uses LockManager for atomic distributed lock across multiple servers
     */
    private function handleTokenRefresh(
        \Magento\Framework\Session\SessionManager $session,
        array $hotaiToken
    ): string {
        $lockKey = $this->getLockKey();
        $tokenCacheKey = $this->getTokenCacheKey();

        // First, check if token was already refreshed by another server (from centralized cache)
        $cachedToken = $this->getTokenFromCache();
        if ($cachedToken && !$this->isTokenExpiredOrNearExpiry($cachedToken)) {
            $this->hotaiAuthService->writeLog('Found fresh token in centralized cache, syncing to session');
            $this->syncCachedTokenToSession($session, $cachedToken);
            return $cachedToken['accessToken'];
        }

        // Try to acquire atomic distributed lock
        $lockAcquired = $this->acquireDistributedLock($lockKey);

        if (!$lockAcquired) {
            // Another request (possibly on another server) is refreshing
            return $this->waitForRefreshAndGetToken($session, $lockKey);
        }

        try {
            // Double-check from centralized cache after acquiring lock
            $cachedToken = $this->getTokenFromCache();
            if ($cachedToken && !$this->isTokenExpiredOrNearExpiry($cachedToken)) {
                $this->hotaiAuthService->writeLog('Found fresh token in cache after lock (double-check), syncing to session');
                $this->syncCachedTokenToSession($session, $cachedToken);
                $this->releaseDistributedLock($lockKey);
                return $cachedToken['accessToken'];
            }

            // Perform the actual refresh
            $refreshedToken = $this->performTokenRefresh($session, $hotaiToken);
            $this->releaseDistributedLock($lockKey);

            return $refreshedToken;
        } catch (\Exception $e) {
            $this->releaseDistributedLock($lockKey);
            $this->hotaiAuthService->writeLog('Exception during token refresh: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Acquire distributed lock using LockManager (atomic operation)
     * This uses Redis SETNX internally for true atomic locking
     */
    private function acquireDistributedLock(string $lockKey): bool
    {
        $acquired = $this->lockManager->lock($lockKey, self::LOCK_TTL);

        if ($acquired) {
            $this->hotaiAuthService->writeLog("Acquired atomic distributed lock: {$lockKey}");
        }

        return $acquired;
    }

    /**
     * Release distributed lock
     */
    private function releaseDistributedLock(string $lockKey): void
    {
        $this->lockManager->unlock($lockKey);
        $this->hotaiAuthService->writeLog("Released distributed lock: {$lockKey}");
    }

    /**
     * Check if distributed lock is active
     */
    private function isDistributedLockActive(string $lockKey): bool
    {
        return $this->lockManager->isLocked($lockKey);
    }

    /**
     * Save token to centralized Redis cache for cross-server synchronization
     */
    private function saveTokenToCache(array $tokenData): void
    {
        $cacheKey = $this->getTokenCacheKey();

        // Ensure expiredAt is stored as string for serialization
        $tokenDataToCache = $tokenData;
        if (isset($tokenDataToCache['expiredAt']) && $tokenDataToCache['expiredAt'] instanceof Carbon) {
            $tokenDataToCache['expiredAt'] = $tokenDataToCache['expiredAt']->toIso8601String();
        }

        try {
            $this->cache->save(
                json_encode($tokenDataToCache),
                $cacheKey,
                ['hotai_token'],
                self::TOKEN_CACHE_TTL
            );
            $this->hotaiAuthService->writeLog("Saved token to centralized cache: {$cacheKey}");
        } catch (\Exception $e) {
            $this->hotaiAuthService->writeLog("Failed to save token to cache: " . $e->getMessage());
        }
    }

    /**
     * Get token from centralized Redis cache
     */
    private function getTokenFromCache(): ?array
    {
        $cacheKey = $this->getTokenCacheKey();

        try {
            $data = $this->cache->load($cacheKey);
            if ($data === false) {
                return null;
            }

            $tokenData = json_decode($data, true);
            if (!$tokenData || !isset($tokenData['accessToken'])) {
                return null;
            }

            // Convert expiredAt back to Carbon for consistency
            if (isset($tokenData['expiredAt']) && is_string($tokenData['expiredAt'])) {
                $tokenData['expiredAt'] = Carbon::parse($tokenData['expiredAt']);
            }

            return $tokenData;
        } catch (\Exception $e) {
            $this->hotaiAuthService->writeLog("Failed to get token from cache: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync cached token to local session
     */
    private function syncCachedTokenToSession(
        \Magento\Framework\Session\SessionManager $session,
        array $cachedToken
    ): void {
        $session->setHotaiToken($cachedToken);
    }

    /**
     * Perform the actual token refresh API call
     */
    private function performTokenRefresh(
        \Magento\Framework\Session\SessionManager $session,
        array $hotaiToken
    ): string {
        try {
            $this->hotaiAuthService->writeLog('Refreshing Hotai token...');
            $refreshToken = $this->hotaiAuthService->refreshToken(
                $hotaiToken['accessToken'],
                $hotaiToken['refreshToken']
            );
        } catch (HotaiAuthException $e) {
            $errorMessage = $e->getMessage();
            $this->hotaiAuthService->writeLog('Hotai beforeGetHotaiToken refreshToken error: ' . $errorMessage);

            // Check if error is "refresh token already used" (race condition occurred)
            if ($this->isTokenAlreadyUsedError($errorMessage)) {
                return $this->handleTokenAlreadyUsedError($session);
            }

            return '';
        }

        if (empty($refreshToken)) {
            return '';
        }

        // Save new token to session and centralized cache
        $newTokenData = [
            'tokenType' => $refreshToken['tokenType'],
            'accessToken' => $refreshToken['accessToken'],
            'refreshToken' => $refreshToken['refreshToken'],
            'accessTokenEncrypted' => $refreshToken['accessTokenEncrypted']
                ?? $this->hotaiAuthService->encryptToken($refreshToken['accessToken']),
            'expiredAt' => Carbon::now()->addMinutes(25),
        ];

        // Save to session (this also triggers saveTokenToCache via around__call)
        $session->setHotaiToken($newTokenData);

        $this->hotaiAuthService->writeLog('Hotai token refreshed successfully');

        return $refreshToken['accessToken'];
    }

    /**
     * Check if error message indicates token was already used (race condition)
     */
    private function isTokenAlreadyUsedError(string $errorMessage): bool
    {
        // Check for Chinese message "已使用" (already used) or invalid token errors
        return str_contains($errorMessage, '已使用')
            || str_contains($errorMessage, '無效的')
            || str_contains($errorMessage, 'invalid')
            || str_contains($errorMessage, 'expired');
    }

    /**
     * Handle case when refresh token was already used by another request
     * Try to get the new token from centralized cache (another server may have refreshed it)
     */
    private function handleTokenAlreadyUsedError(\Magento\Framework\Session\SessionManager $session): string
    {
        $this->hotaiAuthService->writeLog('Hotai refresh token already used, attempting to read from centralized cache');

        // Wait a bit for the other request to finish saving to cache
        usleep(300000); // 300ms

        // Try to get token from centralized cache
        $cachedToken = $this->getTokenFromCache();

        if ($cachedToken && isset($cachedToken['accessToken']) && !$this->isTokenExpiredOrNearExpiry($cachedToken)) {
            $this->hotaiAuthService->writeLog('Hotai found refreshed token in centralized cache after error');
            $this->syncCachedTokenToSession($session, $cachedToken);
            return $cachedToken['accessToken'];
        }

        // Retry getting from cache a few more times
        for ($i = 0; $i < 3; $i++) {
            usleep(200000); // 200ms
            $cachedToken = $this->getTokenFromCache();
            if ($cachedToken && isset($cachedToken['accessToken']) && !$this->isTokenExpiredOrNearExpiry($cachedToken)) {
                $this->hotaiAuthService->writeLog('Hotai found refreshed token in cache on retry ' . ($i + 1));
                $this->syncCachedTokenToSession($session, $cachedToken);
                return $cachedToken['accessToken'];
            }
        }

        $this->hotaiAuthService->writeLog('Hotai could not recover from already used refresh token error');
        return '';
    }

    /**
     * Wait for another request to finish refreshing and get the new token from centralized cache
     */
    private function waitForRefreshAndGetToken(
        \Magento\Framework\Session\SessionManager $session,
        string $lockKey
    ): string {
        $this->hotaiAuthService->writeLog('Hotai waiting for another request to finish token refresh');

        $waitedTime = 0;
        $maxWaitMicroseconds = self::MAX_WAIT_TIME * 1000000;

        while ($waitedTime < $maxWaitMicroseconds) {
            usleep(self::WAIT_SLEEP_INTERVAL);
            $waitedTime += self::WAIT_SLEEP_INTERVAL;

            // Check if lock was released
            if (!$this->isDistributedLockActive($lockKey)) {
                // Lock released, try to get token from centralized cache
                $cachedToken = $this->getTokenFromCache();
                if ($cachedToken && isset($cachedToken['accessToken']) && !$this->isTokenExpiredOrNearExpiry($cachedToken)) {
                    $this->hotaiAuthService->writeLog('Hotai got refreshed token from cache after lock released');
                    $this->syncCachedTokenToSession($session, $cachedToken);
                    return $cachedToken['accessToken'];
                }
            }
        }

        // Timeout reached, try one last time from cache
        $cachedToken = $this->getTokenFromCache();
        if ($cachedToken && isset($cachedToken['accessToken'])) {
            if (!$this->isTokenExpiredOrNearExpiry($cachedToken)) {
                $this->hotaiAuthService->writeLog('Hotai got refreshed token from cache after wait timeout');
                $this->syncCachedTokenToSession($session, $cachedToken);
                return $cachedToken['accessToken'];
            }

            // Token still expired, sync it anyway and return
            $this->hotaiAuthService->writeLog('Hotai token still expired after waiting, returning cached token');
            $this->syncCachedTokenToSession($session, $cachedToken);
            return $cachedToken['accessToken'];
        }

        // Fallback to session token
        $currentToken = $session->getData()['hotai_token'] ?? null;
        if ($currentToken && isset($currentToken['accessToken'])) {
            $this->hotaiAuthService->writeLog('Hotai returning session token as fallback after wait timeout');
            return $currentToken['accessToken'];
        }

        $this->hotaiAuthService->writeLog('Hotai could not get token after waiting');
        return '';
    }

    /**
     * Generate lock key based on platform (Web vs HotaiApp) and customer ID
     * This key is shared across all servers via Redis
     */
    private function getLockKey(): string
    {
        $customerId = $this->customerSession->getCustomerId();
        $platform = $this->mobileDetect->isHotaiApp() ? 'app' : 'web';

        if ($customerId) {
            return self::LOCK_KEY_PREFIX . $platform . '_' . $customerId;
        }

        // Fallback to session ID if customer not logged in
        return self::LOCK_KEY_PREFIX . $platform . '_session_' . session_id();
    }

    /**
     * Generate cache key for centralized token storage
     * This key is shared across all servers via Redis
     */
    private function getTokenCacheKey(): string
    {
        $customerId = $this->customerSession->getCustomerId();
        $platform = $this->mobileDetect->isHotaiApp() ? 'app' : 'web';

        if ($customerId) {
            return self::TOKEN_CACHE_PREFIX . $platform . '_' . $customerId;
        }

        // Fallback to session ID if customer not logged in
        return self::TOKEN_CACHE_PREFIX . $platform . '_session_' . session_id();
    }

    /**
     * Dispatch event to save latest token
     */
    private function saveLatestHotaiToken(\Magento\Framework\Session\SessionManager $session, $args): void
    {
        $this->eventManager->dispatch(
            'hotai_auth_save_latest_token',
            [
                'hotai_token' => $args[0],
            ]
        );
    }
}
