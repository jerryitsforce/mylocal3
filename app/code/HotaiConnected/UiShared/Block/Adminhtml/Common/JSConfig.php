<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HotaiConnected\UiShared\Block\Adminhtml\Common;

use Magento\Backend\Block\Template;
use Magento\Framework\Session\SessionManagerInterface;
use HotaiConnected\FinancialReconciliation\Helper\SessionEncryptor;

/**
 * 共用資料 Block：提供前端可共用的隱藏欄位資料
 */
class JSConfig extends Template
{
    protected SessionEncryptor $sessionEncryptor;
    protected SessionManagerInterface $sessionManager;

    public function __construct(
        Template\Context $context,
        SessionEncryptor $sessionEncryptor,
        SessionManagerInterface $sessionManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sessionEncryptor = $sessionEncryptor;
        $this->sessionManager = $sessionManager;
    }

    /**
     * 取得 authorization bearer token（後台 admin cookie）
     */
    public function getCookieAdmin(): string
    {
        return $_COOKIE['admin'] ?? '';
    }

    /**
     * 後續可集中擴充共用資料
     */
    public function getSharedData(): array
    {
        return [
            'X-Magento-Auth' => $this->sessionEncryptor->encrypt($this->getCookieAdmin())
        ];
    }
}

