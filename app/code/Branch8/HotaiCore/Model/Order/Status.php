<?php

namespace Branch8\HotaiCore\Model\Order;

use Branch8\HotaiCore\Model\Order\State;

class Status
{
    const STATUS_PENDING = 'pending'; // 用於'new' state, Magento原生
    const STATUS_PENDING_PAYMENT = 'pending_payment'; // 用於'pending_payment' state, Magento原生
    const STATUS_PROCESSING = 'processing'; // 用於'processing' state, Magento原生
    const STATUS_SHIPPING = 'shipping'; // 用於'processing' state, Hotai新增
    const STATUS_ARRIVED = 'arrived'; // 用於'processing' state, Hotai新增
    const STATUS_READYTO_SHIP = 'ready_to_ship'; // 用於'processing' state, Hotai新增
    const STATUS_PARENT_ORDER_FAIELD = 'parent_order_failed';
    const STATUS_TICKET_ARRIVED = 'ticket_arrived'; // 用於'processing' state, Hotai新增
    const STATUS_FRAUD = 'fraud'; // 用於'processing'或'payment_review' state, Magento原生
    const STATUS_PENDING_COMPLETE = 'pending_complete'; // 用於'pending_complete' state, Hotai新增
    const STATUS_COMPLETE = 'complete'; // 用於'complete' state, Magento原生
    const STATUS_CLOSED = 'closed'; // 用於'closed' state, Magento原生
    const STATUS_CANCELED = 'canceled'; // 用於'canceled' state, Magento原生
    const STATUS_HOLDED = 'holded'; // 用於'holded' state, Magento原生
    const STATUS_PAYMENT_REVIEW = 'payment_review'; // 用於'payment_review' state, Magento原生
    const STATUS_PICKED = 'picked';  //用於顧客收到貨物時使用
    const STATUS_TALLYING = 'tallying';  //理貨中
    const STATUS_FAILED_DELIVERY = 'failed_delivery'; //配送失敗
    const STATUS_CANCEL_PENDING = 'cancel_pending'; //取消付款審核中
    const STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE = 'cancel_pending_for_po_rc'; //取消訂單中(因母訂單重建)
    const STATUS_GIFT_INFO_PENDING = 'gift_info_pending'; //禮物訂單未完整更新資訊
    const STATUS_GIFT_INFO_COMPLETE = 'gift_info_complete'; //禮物訂單有完整資訊

    // 母訂單與子訂單的 RMA_STATUS
    const STATUS_RMA_PROCESSING = 'rma_processing'; //退換貨處理中
    const STATUS_RMA_COMPLETED = 'rma_completed'; //退換貨已完成
    const STATUS_RMA_FAILED = 'rma_failed'; //退換貨失敗
    const STATUS_RMA_OTHER = 'rma_other'; //其他
    const STATUS_RMA_RETURN_CANCEL = 'rma_apply_return_cancel'; //退貨取消
    const STATUS_RMA_REPLACE_CANCEL = 'rma_apply_replace_cancel'; //換貨取消


    //換貨
    const STATUS_APPLYING_REPLACE = 'applying_replace';  //換貨申請中
    const STATUS_APPLYING_REPLACE_CANCEL = 'applying_replace_cancel';  //換貨申請取消
    const STATUS_APPLYING_REPLACE_AGREE = 'applying_replace_agree';  //換貨申請同意
    const STATUS_APPLYING_REPLACE_REJECT = 'applying_replace_reject';  //換貨申請拒絕

    const STATUS_REPLACE_SHIP_TO_SUPPLIER = 'replace_ship_to_supplier'; //換貨派車回收

    const STATUS_APPLYING_REPLACE_REVIEW = 'applying_replace_review'; // 換貨待審核
    const STATUS_APPLYING_REPLACE_REVIEW_BY_SUPPLIER = 'applying_replace_review_supplier'; // 換貨審核
    const STATUS_APPLYING_REPLACE_REVIEW_ACCEPT = 'applying_replace_review_accept'; //換貨審核同意 / 待出貨
    const STATUS_APPLYING_REPLACE_REVIEW_REJECT = 'applying_replace_review_reject'; //換貨審核不同意

    const STATUS_PROCESSING_REPLACE_READY_TO_SHIP = 'processing_replace_ready_to_ship'; //待出貨

    const STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER = 'processing_replace_shipped'; //已配送
    const STATUS_PROCESSING_REPLACE_ARRIVED = 'processing_replace_arrived'; //已送達
    const STATUS_REPLACED = 'replaced'; //換貨完成
    const STATUS_REPLACE_FAIL = 'replace_fail'; //換貨失敗
    const STATUS_EXCHANGE_AT_BACKOFFICE = 'exchange_at_backoffice'; //後台換貨
    const STATUS_REPLACE_AGAIN = 'replace_again';//再次換貨
    const STATUS_REPLACE_AGAIN_REJECT = 'replace_again_reject';//拒絕再次換貨
    const STATUS_REPLACE_TO_RETURN = 'replace_to_return';//換貨變退貨

    // 退貨
    const STATUS_APPLYING_RETURN = 'applying_return'; //退貨申請中
    const STATUS_APPLYING_RETURN_CANCEL = 'applying_return_cancel'; //退貨申請取消
    const STATUS_APPLYING_RETURN_ACCEPT = 'applying_return_accept'; //退貨申請同意
    const STATUS_APPLYING_RETURN_REJECT = 'applying_return_reject'; //退貨申請不同意

    const STATUS_APPLYING_RETURN_SHIPPING = 'applying_return_shipping'; //退貨派車回收

    const STATUS_APPLYING_RETURN_REVIEW = 'applying_return_review'; // 退貨待審核
    const STATUS_APPLYING_RETURN_REVIEW_ACCEPT = 'applying_return_review_accept'; //退貨審核同意
    const STATUS_APPLYING_RETURN_REVIEW_REJECT = 'applying_return_review_reject'; //退貨審核不同意

    const STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING = 'processing_return_cancel_before_shipping'; //未出貨取消
    const STATUS_PROCESSING_RETURN_AND_REFUND = 'processing_return_and_refund'; //退貨退款
    const STATUS_PROCESSING_NOT_RETURN_BUT_REFUND = 'processing_not_return_but_refund'; //退款不退貨

    const STATUS_FINANCIAL_STATUS = 'financial_status'; //退款審核中
    const STATUS_FINANCIAL_REVIEW = 'financial_review'; //退款審核中
    const STATUS_FINANCIAL_REVIEW_AGREE = 'financial_review_agree'; //退款審核成功
    const STATUS_FINANCIAL_REVIEW_REJECT = 'financial_review_reject'; //退款審核拒絕

    const STATUS_PROCESSING_REFUND = 'processing_refund'; //退款中

    const STATUS_RETURN_AT_BACKOFFICE = 'return_at_backoffice'; //後台退貨

    const STATUS_PROCESSING_RETURN = 'processing_return'; //退貨處理中
    const STATUS_RETURNED = 'returned'; //退貨完成(退款成功)
    const STATUS_RETURN_REFUND_FAIL = 'return_refund_fail'; //退款失敗
    const STATUS_RETURN_REFUND_MANUAL = 'return_refund_manual'; //人工退款

    const FORMAL_FLOW = [
        self::STATUS_PENDING,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_GIFT_INFO_PENDING,
        self::STATUS_GIFT_INFO_COMPLETE,
        self::STATUS_PROCESSING,
        self::STATUS_TALLYING,
        self::STATUS_SHIPPING,
        self::STATUS_ARRIVED,
        self::STATUS_PICKED,
        self::STATUS_PENDING_COMPLETE,
        self::STATUS_COMPLETE
    ];

    const FORMAL_FLOW_STATE = [
        State::STATE_NEW,
        State::STATE_NEW,
        State::STATE_PROCESSING,
        State::STATE_PROCESSING,
        State::STATE_PROCESSING,
        State::STATE_PROCESSING,
        State::STATE_PROCESSING,
        State::STATE_PROCESSING,
        State::STATE_PROCESSING,
        State::STATE_PENDING_COMPLETE,
        State::STATE_COMPLETE,
    ];

    const REVERSE_FLOW = [
        self::STATUS_RMA_PROCESSING,
        self::STATUS_RMA_COMPLETED,
        self::STATUS_RMA_FAILED,
        self::STATUS_RMA_OTHER,
        self::STATUS_RMA_RETURN_CANCEL,
        self::STATUS_RMA_REPLACE_CANCEL
    ];

    // Display STATUS_RMA_COMPLETED
    const REVERSE_FLOW_COMPLETE = [
        self::STATUS_REPLACED,
        self::STATUS_RETURNED
    ];

    // Display STATUS_RMA_FAILED
    const REVERSE_FLOW_DECLINE = [
        self::STATUS_APPLYING_REPLACE_REJECT,
        self::STATUS_APPLYING_REPLACE_REVIEW_REJECT,
        self::STATUS_APPLYING_RETURN_REJECT,
        self::STATUS_APPLYING_RETURN_REVIEW_REJECT,
        self::STATUS_FINANCIAL_REVIEW_REJECT,
    ];

    // Display STATUS_RMA_PROCESSING
    const REVERSE_FLOW_PROCESSING = [
        self::STATUS_APPLYING_RETURN,
        self::STATUS_APPLYING_RETURN_SHIPPING,
        self::STATUS_REPLACE_SHIP_TO_SUPPLIER,
        self::STATUS_APPLYING_RETURN_REVIEW,
        self::STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING,
        self::STATUS_PROCESSING_NOT_RETURN_BUT_REFUND,
        self::STATUS_FINANCIAL_REVIEW,
        self::STATUS_APPLYING_REPLACE,
        self::STATUS_APPLYING_REPLACE_REVIEW,
        self::STATUS_PROCESSING_REPLACE_READY_TO_SHIP,
        self::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER,
        self::STATUS_PROCESSING_REPLACE_ARRIVED,
        self::STATUS_PROCESSING_RETURN_AND_REFUND,
        self::STATUS_RETURN_REFUND_FAIL,
        self::STATUS_PROCESSING_REFUND,
    ];

    const RETURN_FLOW = [
        self::STATUS_RETURNED,
        self::STATUS_APPLYING_RETURN,
        self::STATUS_APPLYING_RETURN_SHIPPING,
        self::STATUS_REPLACE_SHIP_TO_SUPPLIER,
        self::STATUS_APPLYING_RETURN_REVIEW,
        self::STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING,
        self::STATUS_PROCESSING_NOT_RETURN_BUT_REFUND,
        self::STATUS_FINANCIAL_REVIEW,
        self::STATUS_APPLYING_REPLACE,
        self::STATUS_APPLYING_REPLACE_REVIEW,
        self::STATUS_PROCESSING_REPLACE_READY_TO_SHIP,
        self::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER,
        self::STATUS_PROCESSING_REPLACE_ARRIVED,
        self::STATUS_PROCESSING_RETURN_AND_REFUND,
        self::STATUS_RETURN_REFUND_FAIL,
        self::STATUS_PROCESSING_REFUND,
    ];
}