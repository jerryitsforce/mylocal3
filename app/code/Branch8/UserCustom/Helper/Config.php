<?php

namespace Branch8\UserCustom\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    const USER_ROLE = "user_role/general_settings/user_role";

    /**
     * @return string[]
     */
    public function getUserRoles() {
        $value =  $this->scopeConfig->getValue(self::USER_ROLE);
        return explode(',', $value);
    }
}
