<?php

namespace Branch8\Rma\Helper\Config;

use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCore\Model\Order\State as OrderState;
use Branch8\Rma\Model\Rma\Status as RmaStatus;

class StatusLabel
{
    /**
     * getCustomerRmaStatusTitle 前台 rma 標籤
     *
     * @param  int|string $status
     * @return string
     */
    public function getCustomerRmaStatusTitle($status)
    {
        switch ($status) {
            /**
             * 退貨
             */
            //退貨申請中
            case RmaStatus::RETURN_APPLY_PROCESSING:
            case OrderStatus::STATUS_APPLYING_RETURN:
                $rmaStatus = OrderState::STATE_APPLYING_RETURN;
                break;

            //退貨申請取消
            case RmaStatus::RETURN_APPLY_CANCEL:
            case OrderStatus::STATUS_APPLYING_RETURN_CANCEL:
                $rmaStatus = OrderState::STATE_RETURNED_CANCEL;
                break;

            //派車回收
            case RmaStatus::RETURN_SHIPPING:
            case OrderStatus::STATUS_APPLYING_RETURN_SHIPPING:
                $rmaStatus = OrderState::STATE_PROCESSING_SHIPPING;
                break;

            //檢驗中
            case RmaStatus::RETURN_APPLY_AGREE:
            case RmaStatus::RETURN_REVIEW_PROCESSING:
            case OrderStatus::STATUS_APPLYING_RETURN_ACCEPT:
            case OrderStatus::STATUS_APPLYING_RETURN_REVIEW:
                $rmaStatus = OrderState::STATE_PROCESSING_RETURN_REVIEW;
                break;

            //退款中
            case RmaStatus::RETURN_REVIEW_AGREE:
            case RmaStatus::RETURN_CANCEL_BEFORE_SHIPPING:
            case RmaStatus::RETURN_GOODS_AND_REFUND:
            case RmaStatus::NOT_RETURN_GOODS_BUT_REFUND:
            case RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING:
            case RmaStatus::RETURN_FINANCIAL_REVIEW_AGREE:
            case RmaStatus::RETURN_REFUND_PROCESSING:
            case OrderStatus::STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING:
            case OrderStatus::STATUS_PROCESSING_RETURN_AND_REFUND:
            case OrderStatus::STATUS_PROCESSING_NOT_RETURN_BUT_REFUND:
            case OrderStatus::STATUS_FINANCIAL_REVIEW:
            case OrderStatus::STATUS_FINANCIAL_REVIEW_AGREE:
            case OrderStatus::STATUS_PROCESSING_REFUND:
                $rmaStatus = OrderState::STATE_PROCESSING_REFUND;
                break;

            //退貨審核失敗
            case RmaStatus::RETURN_REVIEW_DECLINE:
            case RmaStatus::RETURN_FINANCIAL_REVIEW_DECLINE:
            case OrderStatus::STATUS_APPLYING_RETURN_REVIEW_REJECT:
            case OrderStatus::STATUS_FINANCIAL_REVIEW_REJECT:
                $rmaStatus = OrderState::STATE_RETURNED_FAILED;
                break;

            //退貨申請失敗
            case RmaStatus::RETURN_APPLY_DECLINE:
            case OrderStatus::STATUS_APPLYING_RETURN_REJECT:
                $rmaStatus = OrderState::STATE_APPLYING_RETURN_FAIL;
                break;

            //已退款
            case RmaStatus::RETURNED:
            case OrderStatus::STATUS_RETURNED:
            case RmaStatus::RETURN_FINANCIAL_MANUAL_REFUND_SUCCESS:
            case OrderStatus::STATUS_RETURN_REFUND_MANUAL:
                $rmaStatus = OrderState::STATE_RETURNED;
                break;

            //退款失敗
            case RmaStatus::RETURN_REFUND_FAIL:
            case OrderStatus::STATUS_RETURN_REFUND_FAIL:
                $rmaStatus = OrderState::STATE_RETURNED_FAILED;
                break;

            /**
             * 換貨
             */
            //換貨申請
            case RmaStatus::REPLACE_APPLY_PROCESSING:
            case OrderStatus::STATUS_APPLYING_REPLACE:
                $rmaStatus = OrderState::STATE_APPLYING_REPLACE;
                break;

            //換貨取消
            case RmaStatus::REPLACE_APPLY_CANCEL:
            case OrderStatus::STATUS_APPLYING_REPLACE_CANCEL:
                $rmaStatus = OrderState::STATE_REPLACE_CANCEL;
                break;

            //換貨申請失敗
            case RmaStatus::REPLACE_APPLY_DECLINE:
            case OrderStatus::STATUS_APPLYING_REPLACE_REJECT:
                $rmaStatus = OrderState::STATE_APPLYING_REPLACE_FAIL;
                break;
            
            //派車回收
            case RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER:
            case OrderStatus::STATUS_REPLACE_SHIP_TO_SUPPLIER:
                $rmaStatus = OrderState::STATE_PROCESSING_REPLACE_SHIP_TO_SUP;
                break;

            //檢驗中
            case RmaStatus::REPLACE_REVIEW_PROCESSING:
            case OrderStatus::STATUS_APPLYING_REPLACE_REVIEW:
            case RmaStatus::REPLACE_REVIEW_AGREE:
            case OrderStatus::STATUS_APPLYING_REPLACE_REVIEW_ACCEPT:
            case RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER:
            case OrderStatus::STATUS_PROCESSING_REPLACE_READY_TO_SHIP:
                $rmaStatus = OrderState::STATE_PROCESSING_REPLACE_REVIEW;
                break;

            //換貨審核失敗
            case RmaStatus::REPLACE_REVIEW_DECLINE:
            case OrderStatus::STATUS_APPLYING_REPLACE_REVIEW_REJECT:
                $rmaStatus = OrderState::STATE_REPLACE_FAILED;
                break;

            //配送中
            case RmaStatus::REPLACE_SHIPPING_TO_CUSTOMER:
            case OrderStatus::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER:
                $rmaStatus = OrderState::STATE_PROCESSING_REPLACE_SHIPPING;
                break;


            //已送達
            case RmaStatus::REPLACE_SHIPPING_ARRIVED:
            case OrderStatus::STATUS_PROCESSING_REPLACE_ARRIVED:
                $rmaStatus = OrderState::STATE_REPLACE_ARRIVED;
                break;

            //換貨完成
            case RmaStatus::REPLACE_COMPLETE:
            case OrderStatus::STATUS_REPLACED:
                $rmaStatus = OrderState::STATE_REPLACED;
                break;

            default:
                $rmaStatus = '';
                break;
        }

        return $rmaStatus;
    }
    /**
     * getAdminAndSellerPanelRmaStatusTitle 後台 RMA 標籤
     *
     * @param  int $status
     * @return string
     */
    public function getAdminAndSellerPanelRmaStatusTitle($status)
    {
        switch ($status) {
            /**
             * 退貨
             */
            //退貨申請
            case RmaStatus::RETURN_APPLY_PROCESSING:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN;
                break;
            case RmaStatus::RETURN_APPLY_CANCEL:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_CANCEL;
                break;
            case RmaStatus::RETURN_APPLY_AGREE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_ACCEPT;
                break;
            case RmaStatus::RETURN_APPLY_DECLINE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_REJECT;
                break;
            case RmaStatus::RETURN_SHIPPING:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_SHIPPING;
                break;

            //進入退貨審核流程
            case RmaStatus::RETURN_REVIEW_PROCESSING:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_REVIEW;
                break;
            case RmaStatus::RETURN_REVIEW_AGREE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_REVIEW_ACCEPT;
                break;
            case RmaStatus::RETURN_REVIEW_DECLINE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_RETURN_REVIEW_REJECT;
                break;

            //退貨審核通過後
            case RmaStatus::RETURN_CANCEL_BEFORE_SHIPPING:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING;
                break;
            case RmaStatus::RETURN_GOODS_AND_REFUND:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_RETURN_AND_REFUND;
                break;
            case RmaStatus::NOT_RETURN_GOODS_BUT_REFUND:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_NOT_RETURN_BUT_REFUND;
                break;

            //進入財務審核
            case RmaStatus::RETURN_FINANCIAL_STATUS:
                $rmaStatus = OrderStatus::STATUS_FINANCIAL_STATUS;
                break;

            case RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING:
                $rmaStatus = OrderStatus::STATUS_FINANCIAL_REVIEW;
                break;
            case RmaStatus::RETURN_FINANCIAL_REVIEW_AGREE:
                $rmaStatus = OrderStatus::STATUS_FINANCIAL_REVIEW_AGREE;
                break;
            case RmaStatus::RETURN_FINANCIAL_REVIEW_DECLINE:
                $rmaStatus = OrderStatus::STATUS_FINANCIAL_REVIEW_REJECT;
                break;

            // 進入退款排程
            case RmaStatus::RETURN_REFUND_PROCESSING:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_REFUND;
                break;

            //退款完成/成功
            case RmaStatus::RETURNED:
                $rmaStatus = OrderStatus::STATUS_RETURNED;
                break;

            //退款失敗
            case RmaStatus::RETURN_REFUND_FAIL:
                $rmaStatus = OrderStatus::STATUS_RETURN_REFUND_FAIL;
                break;
            
            //人工退款
            case RmaStatus::RETURN_FINANCIAL_MANUAL_REFUND_SUCCESS:
                $rmaStatus = OrderStatus::STATUS_RETURN_REFUND_MANUAL;
                break;

            /**
             * 換貨
             */
             //換貨申請
            case RmaStatus::REPLACE_APPLY_PROCESSING:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE;
                break;
            case RmaStatus::REPLACE_APPLY_CANCEL:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE_CANCEL;
                break;
            case RmaStatus::REPLACE_APPLY_AGREE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE_AGREE;
                break;
            case RmaStatus::REPLACE_APPLY_DECLINE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE_REJECT;
                break;

            //派車回收
            case RmaStatus::REPLACE_SHIPPING_TO_SUPPLIER:
                $rmaStatus = OrderStatus::STATUS_REPLACE_SHIP_TO_SUPPLIER;
                break;
            
            //檢驗中
            case RmaStatus::REPLACE_REVIEW_PROCESSING:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE_REVIEW;
                break;
            case RmaStatus::REPLACE_REVIEW_AGREE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE_REVIEW_ACCEPT;
                break;

            //檢驗失敗
            case RmaStatus::REPLACE_REVIEW_DECLINE:
                $rmaStatus = OrderStatus::STATUS_APPLYING_REPLACE_REVIEW_REJECT;
                break;

            //換貨進入配送階段
            case RmaStatus::REPLACE_READY_SHIPPING_TO_CUSTOMER:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_REPLACE_READY_TO_SHIP;
                break;
            case RmaStatus::REPLACE_SHIPPING_TO_CUSTOMER:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER;
                break;
            case RmaStatus::REPLACE_SHIPPING_ARRIVED:
                $rmaStatus = OrderStatus::STATUS_PROCESSING_REPLACE_ARRIVED;
                break;

            //換貨完成
            case RmaStatus::REPLACE_COMPLETE:
                $rmaStatus = OrderStatus::STATUS_REPLACED;
                break;
            
            //換貨失敗
            case RmaStatus::REPLACE_FAIL:
                $rmaStatus = OrderStatus::STATUS_REPLACE_FAIL;
                break;

            //再次換貨
            case RmaStatus::REPLACE_AGAIN:
                $rmaStatus = OrderStatus::STATUS_REPLACE_AGAIN;
                break;

            //拒絕再次換貨
            case RmaStatus::REJECT_REPLACE_AGAIN:
                $rmaStatus = OrderStatus::STATUS_REPLACE_AGAIN_REJECT;
                break;

            //換貨變退貨
            case RmaStatus::REPLACE_TO_RETURN:
                $rmaStatus = OrderStatus::STATUS_REPLACE_TO_RETURN;
                break;

            default:
                $rmaStatus = '';
        }

        return $rmaStatus;

    }
}
