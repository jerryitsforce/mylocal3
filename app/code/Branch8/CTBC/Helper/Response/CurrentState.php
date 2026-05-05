<?php

namespace Branch8\CTBC\Helper\Response;

class CurrentState
{
    const AUTHORIZE_FAIL             = '-1'; // 授權失敗
    const CANCEL_ORDER               = '0'; // 訂單已取消
    const AUTHORIZE_SUCCESS          = '1'; //授權成功
    const PAYMENT_REQUEST            = '10'; //已請款(請款結帳中)
    const PAYMENT_REQUEST_PROCESSING = '11'; //已請款(請款中)
    const PAYMENT_REQUEST_SUCCESS    = '12'; //已請款(請款成功)
    const PAYMENT_REQUEST_FAIL       = '13'; //已請款(請款失敗)
    const REFUND_REQUEST             = '20'; //已退款(退款結帳中)
    const REFUND_REQUEST_PROCESSING  = '21'; //已退款(退款中)
    const REFUND_SUCCESS             = '22'; //已退款(退款成功)
    const REFUND_FAIL                = '23'; //已退款(退款失敗)

    const LABEL =
        [
            self::AUTHORIZE_FAIL             => 'Authorize Fail',
            self::CANCEL_ORDER               => 'Cancel Order',
            self::AUTHORIZE_SUCCESS          => 'Authorize Success',
            self::PAYMENT_REQUEST            => 'Payment Request',
            self::PAYMENT_REQUEST_PROCESSING => 'Payment Request Processing',
            self::PAYMENT_REQUEST_SUCCESS    => 'Payment Request Success',
            self::PAYMENT_REQUEST_FAIL       => 'Payment Request Fail',
            self::REFUND_REQUEST             => 'Refund Request',
            self::REFUND_REQUEST_PROCESSING  => 'Refund Request Processing',
            self::REFUND_SUCCESS             => 'Refund Success',
            self::REFUND_FAIL                => 'Refund Fail'
        ];

    const CHINESE_LABEL =
        [
            self::AUTHORIZE_FAIL             => '授權失敗',
            self::CANCEL_ORDER               => '訂單已取消',
            self::AUTHORIZE_SUCCESS          => '授權成功',
            self::PAYMENT_REQUEST            => '已請款(請款結帳中)',
            self::PAYMENT_REQUEST_PROCESSING => '已請款(請款中)',
            self::PAYMENT_REQUEST_SUCCESS    => '已請款(請款成功)',
            self::PAYMENT_REQUEST_FAIL       => '已請款(請款失敗)',
            self::REFUND_REQUEST             => '已退款(退款結帳中)',
            self::REFUND_REQUEST_PROCESSING  => '已退款(退款中)',
            self::REFUND_SUCCESS             => '已退款(退款成功)',
            self::REFUND_FAIL                => '已退款(退款失敗)'
        ];
}
