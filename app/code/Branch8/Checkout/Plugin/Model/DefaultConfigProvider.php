<?php

namespace Branch8\Checkout\Plugin\Model;

class DefaultConfigProvider{

    public function afterGetConfig($subject, $result) {
        $customerData = $result['customerData'];
        if(isset($result['customerData']['custom_attributes']['buyer_email'])){
            $customerData['email'] = $result['customerData']['custom_attributes']['buyer_email']['value'];
            $result['customerData'] = $customerData;
        }
        return $result;
    }

}