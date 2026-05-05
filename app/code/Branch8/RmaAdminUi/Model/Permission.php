<?php

namespace Branch8\RmaAdminUi\Model;

class Permission
{
    const FINAL_APPROVE = 'Webkul_MpRmaSystem::final_approve';
    private \Magento\Framework\AuthorizationInterface $authorization;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(\Magento\Framework\AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * @return bool
     */
    public function canApproveFinanceRma()
    {
        return $this->authorization->isAllowed(self::FINAL_APPROVE);
    }
}
