<?php

namespace Branch8\SellerPermission\Plugin\Webkul\Marketplace\Helper;

class Data
{

    /**
     * @param $subject
     * @param $result
     * @param $actionName
     * @return false|mixed
     */
    public function afterIsAllowedAction($subject, $result, $actionName) {
        if ($actionName == 'marketplace/account/customer') {
            $result = false;
        }
        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @param $actionName
     * @return false|mixed
     */
    public function afterIsAllowed($subject, $result, $actionName) {
        if ($actionName == 'marketplace/account/customer') {
            $result = false;
        }

        return $result;
    }
}