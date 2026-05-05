<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HotaiConnected\UiShared\Helper;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Role Helper
 * 提供管理員用戶角色檢查的業務邏輯
 * 
 * @package HotaiConnected\UiShared\Helper
 */
class Role extends AbstractHelper
{
    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @param Context $context
     * @param Session $authSession
     */
    public function __construct(
        Context $context,
        Session $authSession
    ) {
        parent::__construct($context);
        $this->authSession = $authSession;
    }

    /**
     * Get current admin user role name
     *
     * @return string|null
     */
    public function getUserRoleName()
    {
        $user = $this->authSession->getUser();
        if ($user && $user->getRole()) {
            return $user->getRole()->getRoleName();
        }
        return null;
    }

    /**
     * Get current admin user role ID
     *
     * @return int|null
     */
    public function getUserRoleId()
    {
        $user = $this->authSession->getUser();
        if ($user && $user->getRole()) {
            $roleId = $user->getRole()->getId();
            // 確保返回整數類型
            return $roleId !== null ? (int)$roleId : null;
        }
        return null;
    }

    /**
     * Get current admin user ID
     *
     * @return int|null
     */
    public function getUserId()
    {
        $user = $this->authSession->getUser();
        return $user ? $user->getId() : null;
    }

    /**
     * Check if current user is finance role
     * 
     * @return bool
     */
    public function isFinanceRole()
    {
        $roleName = $this->getUserRoleName();
        if (!$roleName) {
            return false;
        }

        // 財務相關關鍵字（可根據實際角色名稱調整）
        $financeKeywords = ['財務_', '(財務)', 'Finance'];
        
        foreach ($financeKeywords as $keyword) {
            if (stripos($roleName, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current user is curator role
     * 
     * @return bool
     */
    public function isCuratorRole()
    {
        $roleName = $this->getUserRoleName();
        if (!$roleName) {
            return false;
        }

        // 館長相關關鍵字（可根據實際角色名稱調整）
        $curatorKeywords = ['館長_', '(館長)'];
        
        foreach ($curatorKeywords as $keyword) {
            if (stripos($roleName, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current user is dealer role (經銷商)
     * 
     * @return bool
     */
    public function isDealerRole()
    {
        $roleName = $this->getUserRoleName();
        if (!$roleName) {
            return false;
        }

        // 經銷商相關關鍵字（可根據實際角色名稱調整）
        $dealerKeywords = ['經銷商_', '(經銷商)'];
        
        foreach ($dealerKeywords as $keyword) {
            if (stripos($roleName, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current user is PM role (專案經理)
     * 
     * @return bool
     */
    public function isPmRole()
    {
        $roleName = $this->getUserRoleName();
        if (!$roleName) {
            return false;
        }

        // PM 相關關鍵字（可根據實際角色名稱調整）
        $pmKeywords = ['PM'];
        
        foreach ($pmKeywords as $keyword) {
            if (stripos($roleName, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if current user is admin role (管理員)
     * 
     * @return bool
     */
    public function isAdminRole()
    {
        // Admin 角色通常是 role_id = 1
        $roleId = $this->getUserRoleId();
        return $roleId !== null && (int)$roleId === 1;
    }
}

