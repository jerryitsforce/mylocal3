<?php

namespace Branch8\HotaiCore\Model\Order;

class State
{
    const STATE_NEW             = 'new'; // Magento原生
    const STATE_PENDING_PAYMENT = 'pending_payment'; // Magento原生
    const STATE_PROCESSING      = 'processing'; // Magento原生
    const STATE_PENDING_COMPLETE = 'pending_complete'; // Hotai新增
    const STATE_COMPLETE        = 'complete'; // Magento原生
    const STATE_CLOSED          = 'closed'; // Magento原生
    const STATE_CANCELED        = 'canceled'; // Magento原生
    const STATE_HOLDED          = 'holded'; // Magento原生
    const STATE_PAYMENT_REVIEW  = 'payment_review'; // Magento原生
    const STATE_FRAUD  = 'fraud'; // Magento原生
    const STATE_CANCEL_PENDING = 'cancel_pending'; //取消付款審核中
    const STATE_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE = 'cancel_pending_for_po_rc'; //取消訂單中(因母訂單重建)

    //退貨
    const STATE_APPLYING_RETURN = 'applying_return'; //退貨申請中
    const STATE_APPLYING_RETURN_FAIL = 'applying_return_fail'; //退貨申請失敗
    const STATE_PROCESSING_SHIPPING = 'processing_return_shipping'; //派車回收
    const STATE_PROCESSING_RETURN_REVIEW = 'processing_return_review'; //退貨審核中
    const STATE_PROCESSING_REFUND = 'processing_refund'; //退款處理中
    const STATE_RETURNED = 'returned'; //退款完成
    const STATE_RETURNED_FAILED = 'returned_failed'; //退貨失敗
    const STATE_RETURNED_CANCEL = 'returned_cancel'; //退貨取消

    //換貨
    const STATE_APPLYING_REPLACE = 'applying_replace'; //換貨申請中
    const STATE_APPLYING_REPLACE_FAIL = 'applying_replace_fail'; //換貨申請失敗
    const STATE_PROCESSING_REPLACE_SHIP_TO_SUP = 'processing_replace_ship_to_sup'; //派車回收
    const STATE_PROCESSING_REPLACE_REVIEW = 'processing_replace_review'; //換貨審核中
    const STATE_PROCESSING_REPLACE = 'processing_replace'; //換貨處理中
    const STATE_PROCESSING_REPLACE_SHIPPING = 'processing_replace_shipping'; //配送中
    const STATE_PROCESSING_REPLACE_ARRIVED= 'processing_replace_arrived'; //已送達
    const STATE_REPLACED = 'replaced'; //已完成
    const STATE_REPLACE_PICKED = 'replace_picked'; //已取貨
    const STATE_REPLACE_FAILED = 'replace_failed'; //換貨失敗
    const STATE_REPLACE_CANCEL = 'replace_cancel'; //換貨取消
    const STATE_REPLACE_ARRIVED = 'processing_replace_arrived'; //已送達

    const STATE_RMA = 'rma';
}
