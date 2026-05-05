<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HotaiConnected\UiShared\Block\Frontend\Common;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use HotaiConnected\FinancialReconciliation\Helper\SessionEncryptor;
use Branch8\MarketplaceSession\Plugin\SessionManager;

/**
 * 共用資料 Block：提供前端可共用的隱藏欄位資料
 */
class JSConfig extends Template
{
    protected SessionEncryptor $sessionEncryptor;
    protected SessionManagerInterface $sessionManager;
    protected CookieManagerInterface $cookieManager;

    public function __construct(
        Template\Context $context,
        SessionEncryptor $sessionEncryptor,
        SessionManagerInterface $sessionManager,
        CookieManagerInterface $cookieManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sessionEncryptor = $sessionEncryptor;
        $this->sessionManager = $sessionManager;
        $this->cookieManager = $cookieManager;
    }

    /**
     * 取得 authorization bearer token（前台 customer cookie）
     */
    public function getCookieCustomer(): string
    {
        // 嘗試從 MPSESSION cookie 取得（使用 CookieManager）
        $sessionId = $this->cookieManager->getCookie(SessionManager::SESSION_NAME);
        if ($sessionId) {
            return $sessionId;
        }

        // Fallback 到 $_COOKIE
        return $_COOKIE[SessionManager::SESSION_NAME] ?? '';
    }

    /**
     * 後續可集中擴充共用資料
     */
    public function getSharedData(): array
    {
        $sessionId = $this->getCookieCustomer();
        $encrypted = '';
        
        // 只有在有 session ID 時才進行加密
        if (!empty($sessionId)) {
            $encrypted = $this->sessionEncryptor->encrypt($sessionId);
            // 如果加密失敗，返回空字串而不是 false
            if ($encrypted === false) {
                $encrypted = '';
            }
        }
        
        return [
            'X-Magento-Auth' => $encrypted
        ];
    }
}

