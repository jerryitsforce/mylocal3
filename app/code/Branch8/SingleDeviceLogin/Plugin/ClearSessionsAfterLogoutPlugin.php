<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SingleDeviceLogin\Plugin;

use Branch8\SingleDeviceLogin\Helper\Data;
use Branch8\SingleDeviceLogin\Helper\Data as SingleDeviceHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Area;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Exception\SessionException;
use Branch8\SingleDeviceLogin\Helper\Logger as CustomLogger;
use Magento\Framework\Exception\LocalizedException;

/**
 * Clears previous active sessions after logout
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ClearSessionsAfterLogoutPlugin
{
    /**
     * Array key for all active previous session ids.
     */
    private const PREVIOUS_ACTIVE_SESSIONS = 'previous_active_sessions';

    /**
     * Initialize Dependencies
     *
     * @param Session $customerSession
     * @param SaveHandlerInterface $saveHandler
     * @param StorageInterface $storage
     * @param State $state
     * @param CustomLogger $logger
     */
    public function __construct(
        protected Session $customerSession,
        protected SaveHandlerInterface $saveHandler,
        protected StorageInterface $storage,
        protected State $state,
        protected CustomLogger $logger,
        protected CacheInterface $cache,
        protected Serialize $serializer,
        protected SingleDeviceHelper $helper,
    ) {
    }

    /**
     * Plugin to clear session after logout
     *
     * @param Session $subject
     * @param Session $result
     * @return Session
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLogout(Session $subject, Session $result): Session
    {
//        $isAreaFrontEnd = $this->state->getAreaCode() === Area::AREA_FRONTEND;
//        $previousSessions = $this->storage->getData(self::PREVIOUS_ACTIVE_SESSIONS);
//
//        if ($isAreaFrontEnd && !empty($previousSessions)) {
//            foreach ($previousSessions as $sessionId) {
//
//                try {
//                    if (!$this->isCurrentActiveSession($sessionId)) {
//                        $this->customerSession->start();
//                        $this->saveHandler->destroy($sessionId);
//                        $this->customerSession->writeClose();
//                    }
//                } catch (SessionException $e) {
//                    $this->logger->error($e);
//                }
//
//            }
//            $this->storage->setData(self::PREVIOUS_ACTIVE_SESSIONS, []);
//        }
        return $result;
    }


    public function isCurrentActiveSession(string $sessionId): bool
    {
        if (!$this->helper->isSingleDeviceLoginEnabled()) {
            return false;
        }


        $data = $this->saveHandler->read($sessionId);

        if(empty($data)) {
            return false;
        }

        try {
            $data = $this->decodeMagentoSession($data);
        } catch (\Throwable $e) {
            $this->helper->logDebug(
                sprintf(
                    'Failed to decode session data for session ID %s: %s',
                    $sessionId,
                    $e->getMessage()
                )
            );
            return false;
        }

        $customerId = $data['customer_base']['customer_id'] ?? null;
        $sessionUniqueId = $data['default'][Data::SESSION_UNIQUE_ID_KEY] ?? null;

        if (!$customerId) {
            return false;
        }
        $cacheKey = Data::REDIS_SESSION_KEY_PREFIX . $customerId;
        $activeSessionId = $this->cache->load($cacheKey);

        $this->helper->logDebug("Is current active session check: " . $activeSessionId && $activeSessionId === $sessionUniqueId);

        return $activeSessionId && $activeSessionId === $sessionUniqueId;
    }

    public function decodeMagentoSession(string $str): array
    {
        $result = [];
        $offset = 0;
        $len = strlen($str);

        while ($offset < $len) {
            if (!preg_match('/\G([a-zA-Z0-9_]+)\|/A', $str, $m, 0, $offset)) {
                break;
            }
            $name = $m[1];
            $offset += strlen($m[0]);

            try {
                $data = unserialize(substr($str, $offset), ['allowed_classes' => false]);

            } catch (\Throwable $e) {
                $this->helper->logDebug(
                    sprintf(
                        'Failed to unserialize session data for key %s: %s',
                        $name,
                        $e->getMessage()
                    )
                );
                $data = null;
            }
            $result[$name] = $data;

            $offset += strlen(serialize($data));
        }

        return $result;
    }
}
