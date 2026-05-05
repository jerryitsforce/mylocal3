<?php

namespace Branch8\Sales\Plugin\Magento\Sales\Model;

class CustomShippingDescription
{

    /**
     * Default return data is carrier_title  - method_name
     * But these are the same, so return only carrier title
     * @param $subject
     * @param $result
     * @return string
     */
    public function afterGetShippingDescription($subject, $result){
        $result = explode('-',(string)$result);
        if(!isset($result[0])){
            return '';
        }
        $carrierTitle = $result[0];
        return trim((string)$carrierTitle);
    }

}