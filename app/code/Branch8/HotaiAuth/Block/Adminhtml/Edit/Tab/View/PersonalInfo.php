<?php

namespace Branch8\HotaiAuth\Block\Adminhtml\Edit\Tab\View;

class PersonalInfo extends \Magento\Customer\Block\Adminhtml\Edit\Tab\View\PersonalInfo
{
    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getLastLogOutDate(){
        $date = $this->getCustomerLog()->getLastLogoutAt();

        if ($date) {
            return $this->formatDate($date, \IntlDateFormatter::MEDIUM, true);
        }
        return __('Never');
    }
}
