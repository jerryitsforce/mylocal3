<?php

namespace Branch8\AdvancedPermissions\Plugin\AmastyRolepermissions\Block\Adminhtml\Role\Tab;

use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Scope as AmRoleScope;

class Scope
{

    /**
     * Whether tab is available
     *
     * @return bool
     */
    public function aroundCanShowTab(AmRoleScope $subject, \Closure $proceed)
    {
        return false;
    }

    /**
     * Whether tab is visible
     *
     * @return bool
     */
    public function aroundIsHidden(AmRoleScope $subject, \Closure $proceed)
    {
        return true;
    }
}
