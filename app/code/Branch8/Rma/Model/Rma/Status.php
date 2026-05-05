<?php

namespace Branch8\Rma\Model\Rma;

use Branch8\Rma\Model\Rma\Status as RmaStatus;

class Status
{

    const RETURN_OR_EXCHANGE_NOT_AVALIABLE = 0;

    /**
     * 是否可以退換貨 每日 00:00 後
     */
    const RETURN_OR_EXCHANGE_AVALIABLE = 1;
    const RMA_PROCESSING = 99;
    const RMA_CANCELED = 100;

    /**
     * Return 退貨流程
     */
    const RETURN_APPLY_PROCESSING = 2; // 退貨申請中
    const RETURN_APPLY_CANCEL = 3; // 退貨取消

    const RETURN_APPLY_AGREE = 4; //退貨申請同意
    const RETURN_APPLY_DECLINE = 5; //退貨申請不同意

    const RETURN_SHIPPING = 6; //退貨派車回收

    const RETURN_REVIEW_PROCESSING = 7; // 退貨審核中
    const RETURN_REVIEW_AGREE = 8; //退貨審核同意
    const RETURN_REVIEW_DECLINE = 9; //退貨審核不同意

    const RETURN_CANCEL_BEFORE_SHIPPING = 10; //未出貨取消
    const RETURN_GOODS_AND_REFUND = 11; //退貨退款
    const NOT_RETURN_GOODS_BUT_REFUND = 12; //退款不退貨

    const RETURN_FINANCIAL_REVIEW_PROCESSING = 13; // 財務退款審核中
    const RETURN_FINANCIAL_REVIEW_AGREE = 14; // 財務退款審核同意
    const RETURN_FINANCIAL_REVIEW_DECLINE = 15; // 財務退款審核不同意

    const RETURN_REFUND_PROCESSING = 16; //進入退款排程
    const RETURNED = 17; //退款成功 - 退款完成/取消授權/取消請款
    const RETURN_REFUND_FAIL = 18; //退款失敗

    const RETURN_FINANCIAL_MANUAL_REFUND_SUCCESS = 19; //人工退款成功 



     /**
     * Replace 換貨流程
     */
    const REPLACE_APPLY_PROCESSING = 21; //換貨申請中
    const REPLACE_APPLY_CANCEL = 22; //換貨取消
    const REPLACE_APPLY_AGREE = 23; //換貨申請同意--> 換貨待審核
    const REPLACE_APPLY_DECLINE = 24; //換貨申請不同意

    const REPLACE_SHIPPING_TO_SUPPLIER = 25; //派車回收
    const REPLACE_REVIEW_PROCESSING = 26; //換貨待審核
    const REPLACE_REVIEW_AGREE = 27; //換貨審核同意 - 同意換貨
    const REPLACE_REVIEW_DECLINE = 28; //換貨審核不同意

    const REPLACE_READY_SHIPPING_TO_CUSTOMER = 29; //待出貨

    const REPLACE_SHIPPING_TO_CUSTOMER = 30; //配送中
    const REPLACE_SHIPPING_ARRIVED = 31; //已送達
    const REPLACE_COMPLETE = 32; //已完成換貨
    const REPLACE_FAIL = 33; //換貨失敗

    const RETURN_FINANCIAL_STATUS = 34; // financial_status; 

    const REPLACE_TO_RETURN = 50; // update application from replace to return
    const RETURN_TO_REPLACE = 51; // update application from return to replace
    const REPLACE_AGAIN = 52; // replace again
    const REJECT_REPLACE_AGAIN = 53; // reject replace again

    /**
     * All Declined Status
     * @return int[]
     */
    public static function declinedStatus()
    {
        return [
            self::RETURN_APPLY_DECLINE,
            self::RETURN_REVIEW_DECLINE,
            self::RETURN_FINANCIAL_REVIEW_DECLINE,
            self::REPLACE_APPLY_DECLINE,
            self::REPLACE_REVIEW_DECLINE
        ];
    }

     /**
     * canCancelTicket
     * @return int[]
     */
    public static function canCancelTicket()
    {
        return [
            // self::RETURN_REVIEW_AGREE,
            self::RETURN_GOODS_AND_REFUND,
        ];
    }
}

?>
