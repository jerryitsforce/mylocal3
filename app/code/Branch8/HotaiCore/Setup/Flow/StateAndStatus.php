<?php
namespace Branch8\HotaiCore\Setup\Flow;

use Branch8\HotaiCore\Model\Order\State as HotaiOrderState;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;

class StateAndStatus
{
    const VERSION_1 =
        [
        //退貨
        HotaiOrderState::STATE_APPLYING_RETURN             => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN, 'label' => 'Applying Return'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_ACCEPT, 'label' => 'Applying Return Accept'],
        ],
        HotaiOrderState::STATE_PROCESSING_RETURN_REVIEW    => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REVIEW, 'label' => 'Applying Return Review'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REVIEW_ACCEPT, 'label' => 'Applying Return Review Accept'],
        ],
        HotaiOrderState::STATE_PROCESSING_REFUND           => [
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING, 'label' => 'Return Cancel Before Shipping'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_RETURN_AND_REFUND, 'label' => 'Return And Refund'],
            ['status' => HotaiOrderStatus::STATUS_FINANCIAL_REVIEW, 'label' => 'Financial Review'],
            ['status' => HotaiOrderStatus::STATUS_FINANCIAL_REVIEW_AGREE, 'label' => 'Financial Review Agree'],
            ['status' => HotaiOrderStatus::STATUS_FINANCIAL_REVIEW_REJECT, 'label' => 'Financial Review Reject'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_REFUND, 'label' => 'Refunding'],
        ],
        HotaiOrderState::STATE_RETURNED                    => [
            ['status' => HotaiOrderStatus::STATUS_RETURNED, 'label' => 'Returned'],
        ],
        HotaiOrderState::STATE_RETURNED_FAILED             => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REJECT, 'label' => 'Applying Return Decline'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REVIEW_REJECT, 'label' => 'Applying Return Review Reject'],
        ],
        HotaiOrderState::STATE_RETURNED_CANCEL             => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_CANCEL, 'label' => 'Applying Return Cancel'],
        ],

        //換貨
        HotaiOrderState::STATE_APPLYING_REPLACE            => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE, 'label' => 'Applying Replace'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_AGREE, 'label' => 'Applying Replace Agree'],
        ],

        HotaiOrderState::STATE_PROCESSING_REPLACE_REVIEW   => [
            ['status' => HotaiOrderStatus::STATUS_REPLACE_SHIP_TO_SUPPLIER, 'label' => 'Shipping to Supplier'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW, 'label' => 'Replace Review Processing'],
        ],

        HotaiOrderState::STATE_PROCESSING_REPLACE          => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW_BY_SUPPLIER, 'label' => 'Replace Review Processing By Supplier'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW_ACCEPT, 'label' => 'Replace Review Accept'],
        ],
        HotaiOrderState::STATE_PROCESSING_REPLACE_SHIPPING => [
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER, 'label' => 'Replace Shipping To Customer'],
        ],
        HotaiOrderState::STATE_REPLACED                    => [
            ['status' => HotaiOrderStatus::STATUS_REPLACED, 'label' => 'Replaced'],
        ],
        HotaiOrderState::STATE_REPLACE_FAILED              => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REJECT, 'label' => 'Replace Apply Reject'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW_REJECT, 'label' => 'Replace Review Reject'],
        ],
        HotaiOrderState::STATE_REPLACE_CANCEL              => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_CANCEL, 'label' => 'Replace Apply Cancel'],
        ],
    ];

    const VERSION_2 =
        [
        //退貨
        HotaiOrderState::STATE_APPLYING_RETURN             => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN, 'label' => 'Applying Return'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_ACCEPT, 'label' => 'Applying Return Accept'],
        ],
        HotaiOrderState::STATE_PROCESSING_RETURN_REVIEW    => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REVIEW, 'label' => 'Applying Return Review'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REVIEW_ACCEPT, 'label' => 'Applying Return Review Accept'],
        ],
        HotaiOrderState::STATE_PROCESSING_SHIPPING         => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_SHIPPING, 'label' => 'Applying Return Shipping'],
        ],
        HotaiOrderState::STATE_PROCESSING_REFUND           => [
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_RETURN_CANCEL_BEFORE_SHIPPING, 'label' => 'Return Cancel Before Shipping'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_RETURN_AND_REFUND, 'label' => 'Return And Refund'],
            ['status' => HotaiOrderStatus::STATUS_FINANCIAL_REVIEW, 'label' => 'Financial Review'],
            ['status' => HotaiOrderStatus::STATUS_FINANCIAL_REVIEW_AGREE, 'label' => 'Financial Review Agree'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_NOT_RETURN_BUT_REFUND, 'label' => 'Not Return but Refund'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_REFUND, 'label' => 'Refunding'],
            ['status' => HotaiOrderStatus::STATUS_RETURN_AT_BACKOFFICE, 'label' => 'Return at BackOffice'],
            ['status' => HotaiOrderStatus::STATUS_RETURN_REFUND_MANUAL, 'label' => 'Refund Manually'],
        ],
        HotaiOrderState::STATE_RETURNED                    => [
            ['status' => HotaiOrderStatus::STATUS_RETURNED, 'label' => 'Returned'],
        ],
        HotaiOrderState::STATE_RETURNED_FAILED             => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REJECT, 'label' => 'Applying Return Decline'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_REVIEW_REJECT, 'label' => 'Applying Return Review Reject'],
            ['status' => HotaiOrderStatus::STATUS_FINANCIAL_REVIEW_REJECT, 'label' => 'Financial Review Reject'],
            ['status' => HotaiOrderStatus::STATUS_RETURN_REFUND_FAIL, 'label' => 'Refund Failed'],
        ],
        HotaiOrderState::STATE_RETURNED_CANCEL             => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_RETURN_CANCEL, 'label' => 'Applying Return Cancel'],
        ],

        //換貨
        HotaiOrderState::STATE_APPLYING_REPLACE            => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE, 'label' => 'Applying Replace'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_AGREE, 'label' => 'Applying Replace Agree'],
        ],

        HotaiOrderState::STATE_PROCESSING_REPLACE_REVIEW   => [
            ['status' => HotaiOrderStatus::STATUS_REPLACE_SHIP_TO_SUPPLIER, 'label' => 'Shipping to Supplier'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW, 'label' => 'Replace Review Processing'],
        ],

        HotaiOrderState::STATE_PROCESSING_REPLACE          => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW_BY_SUPPLIER, 'label' => 'Replace Review Processing By Supplier'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW_ACCEPT, 'label' => 'Replace Review Accept'],
            ['status' => HotaiOrderStatus::STATUS_EXCHANGE_AT_BACKOFFICE, 'label' => 'Exchange at BackOffice'],
            ['status' => HotaiOrderStatus::STATUS_REPLACE_AGAIN, 'label' => 'Replace Again'],
            ['status' => HotaiOrderStatus::STATUS_REPLACE_TO_RETURN, 'label' => 'Replace to Return'],
        ],
        HotaiOrderState::STATE_PROCESSING_REPLACE_SHIPPING => [
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_REPLACE_SHIPPING_TO_CUSTOMER, 'label' => 'Replace Shipping To Customer'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_REPLACE_READY_TO_SHIP, 'label' => 'Ready to Ship'],
            ['status' => HotaiOrderStatus::STATUS_PROCESSING_REPLACE_ARRIVED, 'label' => 'Replace Goods Arrived'],
        ],
        HotaiOrderState::STATE_REPLACED                    => [
            ['status' => HotaiOrderStatus::STATUS_REPLACED, 'label' => 'Replaced'],
        ],
        HotaiOrderState::STATE_REPLACE_FAILED              => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REJECT, 'label' => 'Replace Apply Reject'],
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_REVIEW_REJECT, 'label' => 'Replace Review Reject'],
            ['status' => HotaiOrderStatus::STATUS_REPLACE_FAIL, 'label' => 'Replace Fail'],
            ['status' => HotaiOrderStatus::STATUS_REPLACE_AGAIN_REJECT, 'label' => 'Replace Again Reject'],
        ],
        HotaiOrderState::STATE_REPLACE_CANCEL              => [
            ['status' => HotaiOrderStatus::STATUS_APPLYING_REPLACE_CANCEL, 'label' => 'Replace Apply Cancel'],
        ],

        //Formal Flow
        HotaiOrderState::STATE_NEW                         => [
            ['status' => HotaiOrderStatus::STATUS_PENDING, 'label' => 'Pending'],
        ],
        HotaiOrderState::STATE_PROCESSING                  => [
            ['status' => HotaiOrderStatus::STATUS_PROCESSING, 'label' => 'Processing'],
            ['status' => HotaiOrderStatus::STATUS_TALLYING, 'label' => 'Tallying'],
            ['status' => HotaiOrderStatus::STATUS_SHIPPING, 'label' => 'Shipping'],
            ['status' => HotaiOrderStatus::STATUS_ARRIVED, 'label' => 'Arrived'],
            ['status' => HotaiOrderStatus::STATUS_TICKET_ARRIVED, 'label' => 'Ticket Arrived'],
            ['status' => HotaiOrderStatus::STATUS_PICKED, 'label' => 'Picked'],
            ['status' => HotaiOrderStatus::STATUS_FAILED_DELIVERY, 'label' => 'Failed Delivery'],
        ],
        HotaiOrderState::STATE_COMPLETE                    => [
            ['status' => HotaiOrderStatus::STATUS_COMPLETE, 'label' => 'Complete'],
        ],
        HotaiOrderState::STATE_CLOSED                      => [
            ['status' => HotaiOrderStatus::STATUS_CLOSED, 'label' => 'Closed'],
        ],
        HotaiOrderState::STATE_CANCELED                    => [
            ['status' => HotaiOrderStatus::STATUS_CANCELED, 'label' => 'Canceled'],
        ],

        //RMA
        HotaiOrderState::STATE_RMA                         => [
            ['status' => HotaiOrderStatus::STATUS_RMA_PROCESSING, 'label' => 'Rma Processing'],
            ['status' => HotaiOrderStatus::STATUS_RMA_COMPLETED, 'label' => 'Rma Complete'],
            ['status' => HotaiOrderStatus::STATUS_RMA_FAILED, 'label' => 'Rma Failed'],
            ['status' => HotaiOrderStatus::STATUS_RMA_OTHER, 'label' => 'Rma Other'],
        ],
    ];

    const VERSION_CANCELLATION_PENDING = [
        HotaiOrderState::STATE_CANCEL_PENDING => [
            ['status' => HotaiOrderStatus::STATUS_CANCEL_PENDING, 'label' => 'Cancellation Pending'],
        ],
    ];

    const VERSION_NEW_RMA_CANCEL_STATUS = [
        HotaiOrderState::STATE_RMA => [
            ['status' => HotaiOrderStatus::STATUS_RMA_RETURN_CANCEL, 'label' => 'Rma Return Cancel'],
            ['status' => HotaiOrderStatus::STATUS_RMA_REPLACE_CANCEL, 'label' => 'Rma Replace Cancel'],
        ],
    ];

    const VERSION_PENDING_COMPLETE_STATUS = [
        HotaiOrderState::STATE_PENDING_COMPLETE => [
            ['status' => HotaiOrderStatus::STATUS_PENDING_COMPLETE, 'label' => 'Pending Complete'],
        ],
    ];

    const VERSION_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE = [
        HotaiOrderState::STATE_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE => [
            ['status' => HotaiOrderStatus::STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE, 'label' => 'Cancel Pending for Parent Order Recreate'],
        ],
    ];

    const VERSION_GIFT_ORDER_STATUS = [
        HotaiOrderState::STATE_PROCESSING => [
            ['status' => HotaiOrderStatus::STATUS_GIFT_INFO_PENDING, 'label' => 'Gift Info Pending'],
            ['status' => HotaiOrderStatus::STATUS_GIFT_INFO_COMPLETE, 'label' => 'Gift Info Complete'],
        ],
    ];
}
