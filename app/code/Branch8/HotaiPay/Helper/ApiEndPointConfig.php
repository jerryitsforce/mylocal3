<?php 

namespace Branch8\HotaiPay\Helper;

/**
 * ApiEndPointConfig
 */
class ApiEndPointConfig
{
    const CREDITCARD_ADD = "/creditcard/add";
    const CREDITCARD_ADD_FAST = "/creditcard/fbinding";
    const CREDITCARD_LIST = "/creditcard/card";
    const CREDITCARD_DELETE = "/creditcard/delete/json";
    const CREDITCARD_EDIT_ALIASNAME = "/creditcard/aliasName";
    const CREDITCARD_GET_AFFINITY_LIST = "/creditcard/affinityCard";
    const CHECKOUT_PAY_JSON = "/creditcard/pay/json";
    const CHECKOUT_PAY = "/creditcard/pay";
    const PAYMENT_INQUIRY ="/creditcard/payment/inquiry";
    const MANUALLY_ADD_CREDITCARD_WARNING ="/creditcard/addcardwarning";
    const FAST_ADD_CREDITCARD_WARNING ="/creditcard/fastaddwarning";

    const METHOD = "METHOD";
    const RESPONSE = "RESPONSE";
    const API_AND_NEEDED_RESPONSE_DATA = [
        self::CREDITCARD_ADD => [
            self::METHOD => 'POST'
        ],
        self::CREDITCARD_ADD_FAST => [
            self::METHOD => 'POST'
        ],
        self::CREDITCARD_LIST => [
            self::METHOD => 'GET'
        ],
        self::CREDITCARD_EDIT_ALIASNAME => [
            self::METHOD => 'POST'
        ],
        self::CREDITCARD_DELETE => [
            self::METHOD => 'POST'
        ],
        self::CHECKOUT_PAY => [
            self::METHOD => 'POST'
        ],
        self::CHECKOUT_PAY_JSON => [
            self::METHOD => 'POST'
        ],
        self::PAYMENT_INQUIRY => [
            self::METHOD => 'GET'
        ],
        self::CREDITCARD_GET_AFFINITY_LIST => [
            self::METHOD => 'GET'
        ],
        self::MANUALLY_ADD_CREDITCARD_WARNING => [
            self::METHOD => 'POST'
        ],
        self::FAST_ADD_CREDITCARD_WARNING => [
            self::METHOD => 'POST'
        ]
    ];
    
}
?>