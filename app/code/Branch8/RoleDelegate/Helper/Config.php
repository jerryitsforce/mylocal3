<?php

namespace Branch8\RoleDelegate\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    const ROLE_DELEGATE_USER_ROLE = "role_delegate/general/user_role";

    protected $_cacheUserRoles = [];

    /**
     * @return string[]
     */
    public function getUserRoles() {

        if (!empty($this->_cacheUserRoles)) {
            return $this->_cacheUserRoles;
        }
        $value =  $this->scopeConfig->getValue(self::ROLE_DELEGATE_USER_ROLE);
        return $this->_cacheUserRoles = explode(',', $value);
    }
}
