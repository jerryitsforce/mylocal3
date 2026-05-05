<?php

declare(strict_types=1);

namespace HotaiConnected\Cart\Controller\CvsStore;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Stateless CVS store callback — receives POST from ECPay map, 302 redirects to /cart/.
 *
 * This controller MUST NOT start or modify the PHP session.
 * ECPay posts cross-domain, so SameSite=Lax would cause the browser to drop
 * the PHPSESSID cookie. If Magento starts a new session here, the user's
 * original session (and login state) is lost.
 */
class Callback extends Action implements CsrfAwareActionInterface
{
    const CVS_FIELD_WHITELIST = [
        'storeid',
        'storename',
        'address',
        'servicetype',
        'outside',
        'ship',
    ];

    const QUERY_PREFIX = 'cvs_';

    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        // ── Prevent session side-effects ──
        // Magento's framework may have already started a session.
        // Abort it (discard changes) so we never write a new PHPSESSID cookie.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }

        $data = $this->getRequest()->getParams();

        // Filter whitelisted CVS fields and add prefix
        $query = [];
        foreach (self::CVS_FIELD_WHITELIST as $key) {
            if (isset($data[$key]) && (string)$data[$key] !== '') {
                $query[self::QUERY_PREFIX . $key] = (string)$data[$key];
            }
        }

        $this->logger->info('[CvsStoreCallback] Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        // Build redirect URL: /cart/?cvs_*=...#/checkout
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $redirectUrl = $baseUrl . 'cart/';
        if (!empty($query)) {
            $redirectUrl .= '?' . http_build_query($query);
        }
        $redirectUrl .= '#/checkout';

        $this->logger->info('[CvsStoreCallback] Redirect: ' . $redirectUrl);

        // 302 redirect — bypass Magento response pipeline to prevent Set-Cookie injection
        header_remove('Set-Cookie');
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }
}
