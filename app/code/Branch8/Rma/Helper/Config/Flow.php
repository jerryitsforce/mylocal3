<?php

namespace Branch8\Rma\Helper\Config;

use Branch8\Rma\Model\Rma\Status as RmaStatus;

class Flow
{
    /**
     * @param $status
     * @param $isTicket
     * @param $isReturnAgain
     * @param $canAdjustShippingStatusRole
     * @param $hasCreditNemo
     * @return array
     */
    public function getAllNextStatusOptions(
        $status,
        $isTicket = false,
        $isReturnAgain = false,
        $canAdjustShippingStatusRole = false,
        $hasCreditNemo=false
    )
    {
        /**
         * https://branch8.atlassian.net/browse/HTGO2-1977
         *
         */
        $canAdjustShippingStatus = $hasCreditNemo === false && $canAdjustShippingStatusRole;
        $allStatus = [];
        switch ($status) {
            /**
             * 退貨
             */
            //退貨申請中
            case RmaStatus::RETURN_APPLY_PROCESSING:
                $allStatus = [
                    //同意退貨申請
                    RmaStatus::RETURN_APPLY_AGREE => __('Authorize Return Application'),
                    //未出貨取消
                    RmaStatus::RETURN_CANCEL_BEFORE_SHIPPING => __('Cancel Return Before Shipping'),
                    //退款不退貨
                    RmaStatus::NOT_RETURN_GOODS_BUT_REFUND => __('Not Return Goods But Refund'),
                    //不同意申請退貨
                    RmaStatus::RETURN_APPLY_DECLINE => __('Decline Return Application'),
                ];

                if ($isTicket) {
                    $allStatus = [
                        //退貨退款
                        RmaStatus::RETURN_GOODS_AND_REFUND => __('Retrun Goods and Refund'),
                        //不同意申請退貨
                        RmaStatus::RETURN_APPLY_DECLINE => __('Decline Return Application'),
                    ];
                }
                break;
            case RmaStatus::RETURN_APPLY_DECLINE:
                if ($canAdjustShippingStatus) {
                    $allStatus = [
                        RmaStatus::RETURN_APPLY_PROCESSING => __('Applying Return'),
                        RmaStatus::RETURN_REVIEW_PROCESSING => __('Applying Return Review'),
                    ];
                }
                break;


            //同意申請，退貨審核中
            case RmaStatus::RETURN_REVIEW_PROCESSING:
                $allStatus = [
                    //退貨退款
                    RmaStatus::RETURN_GOODS_AND_REFUND => __('Retrun Goods and Refund'),
                    RmaStatus::RETURN_REVIEW_DECLINE => __('Decline Return Review'),
                ];
                break;

            case RmaStatus::RETURN_SHIPPING:
                $allStatus = [
                    //檢驗中-退貨審核中
                    RmaStatus::RETURN_REVIEW_PROCESSING => __('JUMP TO return review processing'),
                ];
                break;

            //退貨審核同意
            case RmaStatus::RETURN_REVIEW_AGREE:
                $allStatus = [
                    //退貨退款
                    RmaStatus::RETURN_GOODS_AND_REFUND => __('Retrun Goods and Refund'),
                    RmaStatus::RETURN_REVIEW_DECLINE => __('Decline Return Review'),
                ];
                break;
            //RETURN REFUND FAIL
            case RmaStatus::RETURN_REFUND_FAIL:
                if ($canAdjustShippingStatus) {
                    $allStatus = [
                        RmaStatus::RETURN_REVIEW_PROCESSING => __('Applying Return Review'),
                        RmaStatus::RETURN_APPLY_CANCEL => __('Applying Return Cancel')
                    ];
                }
                break;
            /**
             * 換貨
             */
            //換貨申請中
            case RmaStatus::REPLACE_APPLY_PROCESSING:
                $allStatus = [
                    RmaStatus::REPLACE_APPLY_AGREE => __('Authorize Replace Application'),
                    RmaStatus::REPLACE_APPLY_DECLINE => __('Decline Replace Application'),
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                ];
                break;

            //同意申請後 派車回收
            case RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER:
                $allStatus = [
                    RmaStatus::REPLACE_REVIEW_PROCESSING => __('JUMP TO Replace Review'),
                ];
                break;

            //派車回收後，進入換貨審核中
            case RmaStatus::REPLACE_REVIEW_PROCESSING:
                $allStatus = [
                    RmaStatus::REPLACE_REVIEW_AGREE => __('Authorize Replace Review'),
                    RmaStatus::REPLACE_REVIEW_DECLINE => __('Decline Replace Review'),
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                ];
                break;

            //換貨寄給消費者
            case RmaStatus::REPLACE_SHIPPING_TO_CUSTOMER:
                $allStatus = [
                    RmaStatus::REPLACE_SHIPPING_ARRIVED => __('JUMP TO Replace Arrived'),
                ];
                break;

            //已配送
            case RmaStatus::REPLACE_SHIPPING_ARRIVED:
                if ($isReturnAgain) {
                    $allStatus = [
                        RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    ];

                    break;
                }
                $allStatus = [
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    RmaStatus::REPLACE_AGAIN => __('Replace Again'),
                ];
                break;

            //換貨審核不同意
            case RmaStatus::REPLACE_REVIEW_DECLINE:
                if ($isReturnAgain) {
                    $allStatus = [
                        RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    ];

                    break;
                }

                $allStatus = [
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    RmaStatus::REPLACE_AGAIN => __('Replace Again'),
                    RmaStatus::REJECT_REPLACE_AGAIN => __('Reject Replace Again'),
                ];
                break;
            case RmaStatus::REPLACE_APPLY_DECLINE:
                if ($canAdjustShippingStatus) {
                    $allStatus = [
                        RmaStatus::REPLACE_REVIEW_PROCESSING => __('Applying Replace'),
                        RmaStatus::REPLACE_APPLY_CANCEL => __('Applying Replace Cancel'),
                    ];
                }
                break;
            case RmaStatus::REPLACE_FAIL:
                if ($canAdjustShippingStatus) {
                    $allStatus = [
                        //RmaStatus::REPLACE_REVIEW_PROCESSING => __('Applying Replace Review'),
                        //RmaStatus::REPLACE_APPLY_CANCEL => __('Applying Replace Cancel')
                        RmaStatus::RETURN_REVIEW_PROCESSING => __('Applying Return Review'),
                        RmaStatus::RETURN_APPLY_CANCEL => __('Applying Return Cancel')
                    ];
                }
                break;
            default:
                $allStatus = [];
        }

        return $allStatus;
    }

    /**
     * @param $status
     * @param $isTicket
     * @param $isReturnAgain
     * @param $canAdjustShippingStatusRole
     * @param $hasCreditNemo
     * @return array
     */
    public function getAdminAllNextStatusOptions(
        $status,
        $isTicket = false,
        $isReturnAgain = false,
        $canAdjustShippingStatusRole = false,
        $hasCreditNemo = false
    )
    {
        /**
         * https://branch8.atlassian.net/browse/HTGO2-1977
         *
         */
        $canAdjustShippingStatus = $hasCreditNemo === false && $canAdjustShippingStatusRole;
        switch ($status) {
            /**
             * 退貨
             */
            //退貨申請中
            case RmaStatus::RETURN_APPLY_PROCESSING:
            case RmaStatus::RETURN_SHIPPING:
                $allStatus = [
                    //同意退貨申請
                    RmaStatus::RETURN_APPLY_AGREE => __('Authorize Return Application'), // *need to add shipping number record (receive item from customer)
                    //未出貨取消
                    RmaStatus::RETURN_CANCEL_BEFORE_SHIPPING => __('Cancel Return Before Shipping'),
                    //退款不退貨
                    RmaStatus::NOT_RETURN_GOODS_BUT_REFUND => __('Not Return Goods But Refund'),
                    //不同意申請退貨
                    RmaStatus::RETURN_APPLY_DECLINE => __('Decline Return Application'),
                    //取消 RMA
                    RmaStatus::RETURN_APPLY_CANCEL => __('Cancel Return Application'),
                ];
                break;

            //同意申請，檢驗中 - 退貨審核中
            case RmaStatus::RETURN_REVIEW_PROCESSING:
            case RmaStatus::RETURN_REVIEW_AGREE:
            case RmaStatus::RETURN_REVIEW_DECLINE:
                $allStatus = [
                    RmaStatus::RETURN_GOODS_AND_REFUND => __('Retrun Goods and Refund'),
                    RmaStatus::RETURN_REVIEW_DECLINE => __('Decline Return Review'), // *need to add shipping number record (send item back to customer)
                    RmaStatus::RETURN_APPLY_PROCESSING => __('Applying Return'),
                ];
                break;

            //退款財務審核中 or 退款失敗
            case RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING:
            case RmaStatus::RETURN_REFUND_FAIL:
                $allStatus = [
                    RmaStatus::RETURN_FINANCIAL_MANUAL_REFUND_SUCCESS => __('Manually Refund Success.'),
                    //  RmaStatus::RETURN_REVIEW_PROCESSING => __('Applying Return Review.'),
                    //  RmaStatus::RETURN_APPLY_CANCEL => __('Applying Return Cancel')
                ];
                break;

            /**
             * 換貨
             */
            //換貨申請中
            case RmaStatus::REPLACE_APPLY_PROCESSING:
                $allStatus = [
                    RmaStatus::REPLACE_APPLY_AGREE => __('Authorize Replace Application'), // *need to add shipping number record (receive item from customer)
                    RmaStatus::REPLACE_APPLY_DECLINE => __('Decline Replace Application'),
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    RmaStatus::RETURN_APPLY_CANCEL => __('Cancel Return Application'),
                    // and update status to any wanted status

                ];
                break;

            //派車回收後，進入換貨審核中
            case RmaStatus::REPLACE_REVIEW_PROCESSING:
            case RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER:
            case RmaStatus::REPLACE_SHIPPING_TO_CUSTOMER:
                $allStatus = [
                    RmaStatus::REPLACE_REVIEW_AGREE => __('Authorize Replace Review'),
                    RmaStatus::REPLACE_REVIEW_DECLINE => __('Decline Replace Review'),
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                ];
                break;

            //已配送
            case RmaStatus::REPLACE_SHIPPING_ARRIVED:
                if ($isReturnAgain) {
                    $allStatus = [
                        RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    ];

                    break;
                }
                $allStatus = [
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    RmaStatus::REPLACE_AGAIN => __('Replace Again'), // *need to add shipping number record
                ];
                break;

            //換貨審核不同意
            case RmaStatus::REPLACE_REVIEW_DECLINE:
                if ($isReturnAgain) {
                    $allStatus = [
                        RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    ];

                    break;
                }
                $allStatus = [
                    RmaStatus::REPLACE_TO_RETURN => __('Replace to Return'),
                    RmaStatus::REPLACE_AGAIN => __('Replace Again'), // *need to add shipping number record
                    RmaStatus::REJECT_REPLACE_AGAIN => __('Reject Replace Again'),
                ];
                break;

            case RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER:
                $allStatus = [
                    RmaStatus::REPLACE_REVIEW_PROCESSING => __('JUMP TO Replace Review'),
                    RmaStatus::RETURN_APPLY_PROCESSING => __('Applying Return'),

                ];
                break;

            case RmaStatus::REPLACE_FAIL:
                $allStatus=[];
                if ($canAdjustShippingStatus) {
                    $allStatus = [
                        RmaStatus::RETURN_REVIEW_PROCESSING => __('Applying Return Review'),
                        RmaStatus::REPLACE_APPLY_CANCEL => __('Applying Replace Cancel'),
                    ];
                }
                break;

            default:
                $allStatus = [];
        }

        return $allStatus;
    }

    /**
     * getNextStatus
     *
     * @param string|int $status
     * @param bool $naturalPerson
     * @return string|int
     */
    public function getNextStatus($status, $naturalPerson = true)
    {
        switch ($status) {
            case RmaStatus::RETURN_APPLY_AGREE: //退貨申請同意 --> 退貨待審核
                $nextStatus = RmaStatus::RETURN_SHIPPING;
                break;
            case RmaStatus::REPLACE_APPLY_AGREE: //換貨申請同意 --> 派車回收
                $nextStatus = RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER;
                break;
            case RmaStatus::REPLACE_REVIEW_AGREE: //換貨審核同意-->待出貨
                $nextStatus = RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER;
                break;
            case RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER: //待出貨-->配送中
                $nextStatus = RmaStatus::REPLACE_SHIPPING_TO_CUSTOMER;
                break;
            case RmaStatus::REPLACE_SHIPPING_TO_CUSTOMER: //配送中 -> 已送達
                $nextStatus = RmaStatus::REPLACE_SHIPPING_ARRIVED;
                break;
            case RmaStatus::REPLACE_TO_RETURN: //換貨變退貨
                $nextStatus = RmaStatus::RETURN_APPLY_PROCESSING;
                break;
            case RmaStatus::RETURN_TO_REPLACE: //退貨變換貨
                $nextStatus = RmaStatus::REPLACE_APPLY_PROCESSING;
                break;
            case RmaStatus::REPLACE_AGAIN: //再次換貨
                $nextStatus = RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER;
                break;
            case RmaStatus::RETURN_FINANCIAL_REVIEW_AGREE: //退款審核同意
                $nextStatus = RmaStatus::RETURN_REFUND_PROCESSING;
                break;
            default:
                $nextStatus = $status;
        }

        return $nextStatus;

    }

    /**
     * isRefundAvaliableStatus
     *
     * @param string|int $status
     * @return bool
     */
    public function isRefundAvaliableStatus($status)
    {

        $canRefundStatus = [
            RmaStatus::RETURN_CANCEL_BEFORE_SHIPPING,
            RmaStatus::NOT_RETURN_GOODS_BUT_REFUND,
            RmaStatus::RETURN_GOODS_AND_REFUND,
        ];

        return in_array($status, $canRefundStatus);
    }

    /**
     * isReadyToBeShippedStatus
     *
     * @param string|int $status
     * @return bool
     */
    public function isReadyToBeShippedStatus($status)
    {

        $canBeShipped = [
            RmaStatus::RETURN_SHIPPING,
            RmaStatus::REPLACE_REVIEW_AGREE,
            RmaStatus::RETURN_REVIEW_DECLINE,
            RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER,
            RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER,
            RmaStatus::REPLACE_REVIEW_DECLINE,
        ];

        return in_array($status, $canBeShipped);
    }
}
