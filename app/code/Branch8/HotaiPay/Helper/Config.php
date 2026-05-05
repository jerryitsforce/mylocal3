<?php 

namespace Branch8\HotaiPay\Helper;

/**
 * Config
 */
class Config
{
    const HOST_PAYMENT = "hotaipay/host/hotai_payment";
    const HOST_CREDIT_CARD_FAST = "hotaipay/host/hotai_credit_card_fast";
    const HOST_CREDIT_CARD_MANUAL = "hotaipay/host/hotai_credit_card_manual";
    const HOST_DIVIDED_BRANCH = "hotaipay/host/hotai_divided_branch";
    const HOST_CHECKOUT = "hotaipay/host/hotai_checkout";

    const KEY_APPID = "hotaipay/key/appid";
    const KEY_AES_KEY = "hotaipay/key/aes_key";
    const KEY_AES_IV = "hotaipay/key/aes_iv";

    const CHECKOUT_MERCHANT_ID = "hotaipay/checkout/merchant_id";
    const CHECKOUT_MER_ID = "hotaipay/checkout/mer_id";
    const CHECKOUT_TERMINAL_ID = "hotaipay/checkout/terminal_id";

    const ORDER_NEW = "payment/hotaipay/order_status";
    const ORDER_SUCCESS = "payment/hotaipay/order_success";
    const ORDER_FAIL = "payment/hotaipay/order_fail";
    
}
?>