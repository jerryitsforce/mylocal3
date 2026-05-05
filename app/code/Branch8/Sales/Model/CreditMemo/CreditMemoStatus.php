<?php

namespace Branch8\Sales\Model\CreditMemo;

class CreditMemoStatus
{
    const DEFAULT = 0;
    const REFUND_SUCCESS = 1;
    const REFUND_FAIL = 2;
    const FINANCIAL_REVIEW = 3;

    public static function getCreditMemoStatus($value) {
        switch($value) {
            case self::REFUND_SUCCESS:
                return __('REFUND SUCCESS');
            case self::REFUND_FAIL:
                return __('REFUND FAIL');
            case self::FINANCIAL_REVIEW:
                return __('FINANCIAL REVIEWING');
            default:
                return __('REFUND PROCESSING');
        }

    }
}