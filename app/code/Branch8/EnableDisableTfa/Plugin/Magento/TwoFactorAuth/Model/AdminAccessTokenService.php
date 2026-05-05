<?php


namespace Branch8\EnableDisableTfa\Plugin\Magento\TwoFactorAuth\Model;


class AdminAccessTokenService
{
    protected $adminTokenService;
    protected $tfaHelper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Integration\Api\AdminTokenServiceInterface $adminTokenService,
        \Branch8\EnableDisableTfa\Helper\Data $tfaHelper
    ) {
        $this->adminTokenService = $adminTokenService;
        $this->tfaHelper = $tfaHelper;
    }

    public function aroundCreateAdminAccessToken(
        \Magento\TwoFactorAuth\Model\AdminAccessTokenService $subject,
        \Closure $proceed,
        $username,
        $password
    ) {
        if($this->tfaHelper->isEnabled()) {
            $result = $proceed($username, $password);
            return $result;
        } else {
            $adminToken = $this->adminTokenService->createAdminAccessToken($username, $password);
            return $adminToken;
        }
    }
}