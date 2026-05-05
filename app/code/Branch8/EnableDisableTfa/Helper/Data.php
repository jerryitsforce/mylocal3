<?php
namespace Branch8\EnableDisableTfa\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const TWOFACTOR_AUTH_ENABLED = "twofactorauth/general/enabled";
    
    public function isEnabled() {
        return $this->scopeConfig->isSetFlag(self::TWOFACTOR_AUTH_ENABLED);
    }
}
