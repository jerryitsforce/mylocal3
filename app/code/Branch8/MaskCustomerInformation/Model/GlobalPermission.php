<?php
declare(strict_types=1);


namespace Branch8\MaskCustomerInformation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class GlobalPermission implements PermissionInterface
{
    const XML_PATH_SHOW_SELLER_SENSITIVE_INFORMATION = 'marketplace/general_settings/show_sensitive_customer_information';
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function canView()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_SHOW_SELLER_SENSITIVE_INFORMATION);
    }
}
