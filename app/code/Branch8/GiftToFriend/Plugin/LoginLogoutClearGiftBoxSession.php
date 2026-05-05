<?php
namespace Branch8\GiftToFriend\Plugin;

class LoginLogoutClearGiftBoxSession
{

    public function afterSetCustomerDataAsLoggedIn($subject, $result, $customer)
    {
        $result->unsGuestGiftBoxSession();
        return $result;
    }

    public function afterLogout($subject, $result){
        $result->unsGuestGiftBoxSession();
        return $result;
    }
}