<?php
declare(strict_types=1);


namespace Branch8\MaskCustomerInformation\Model;

class AdminPermission implements PermissionInterface
{
    const ACL_PATH = 'Magento_Customer::view_sensitive_information';
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
    public function canView()
    {
        return $this->authorization->isAllowed(self::ACL_PATH);
    }
}
