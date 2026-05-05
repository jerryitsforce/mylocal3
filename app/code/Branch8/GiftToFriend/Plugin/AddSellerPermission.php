<?php
namespace Branch8\GiftToFriend\Plugin;

class AddSellerPermission
{
    public function afterGetAllPermissionTypes($subject, $result){
        $result['gift-order/marketplace/giftorder'] = __('View Gift Order');

        return $result;
    }
}