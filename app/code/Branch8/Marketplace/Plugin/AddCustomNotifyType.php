<?php

namespace Branch8\Marketplace\Plugin;

class AddCustomNotifyType{

    public function afterGetAllTypes($subject, $result){
        $result[\Webkul\Marketplace\Model\Notification::TYPE_CUSTOM] = __('System Information');
        return $result;
    }

}