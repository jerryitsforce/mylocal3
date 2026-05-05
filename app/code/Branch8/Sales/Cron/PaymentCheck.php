<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\HotaiPay\Model\Payment\Update;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Exception;
use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Model\OrderManagement;
use Branch8\CTBC\Helper\Response\CurrentState;
use Branch8\HotaiPay\Helper\CacheLock as HotaiPayCacheLock;

class PaymentCheck
{

    const LOG_PATH = 'Sales/PaymentCheck';

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory */
    protected $parentOrderCollectionFactory;

    /** @var \Branch8\HotaiPay\Model\Payment\Update $update */
    protected $update;

    /** @var \Branch8\CTBC\Model\Api $api */
    protected $api;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var HotaiPayCacheLock */
    protected $hotaiPayCacheLock;

    protected $orderManagement;

    public function __construct(
        CollectionFactory $parentOrderCollectionFactory,
        Update $update,
        Api $api,
        HotaiCoreCommon $hotaiCoreCommon,
        OrderManagement $orderManagement,
        HotaiPayCacheLock $hotaiPayCacheLock
    ) {
        $this->update = $update;
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->api = $api;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->orderManagement = $orderManagement;
        $this->hotaiPayCacheLock = $hotaiPayCacheLock;
    }

    public function execute()
    {
        try {
            $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
            $to = date('Y-m-d H:i:s');

            $collection = $this->parentOrderCollectionFactory->create()
                ->joinDetail(
                    [
                        '*',
                    ]
                )->addFieldToFilter(
                'detail.status',
                HotaiStatus::STATUS_PENDING_PAYMENT
            )->addFieldToFilter(
                'detail.payment_method',
                \Branch8\HotaiPay\Model\Payment\HotaiPay::CODE
            )->addFieldToFilter(
                'created_at', array('from' => $yesterday, 'to' => $to));

            foreach ($collection as $parentOrder) {
                //If there is no increment id then return
                if (!$parentOrder->getIncrementId()) {
                    continue;
                }

                //log parent order id
                $this->hotaiCoreCommon->writeLog(
                    "[ParentOrderId] " . $parentOrder->getIncrementId(). ' - Start Inquiry',
                    self::LOG_PATH
                );

                $this->api->setOrderInfo($parentOrder);
                $inquiryResult = $this->orderManagement->inquiryOrderStatus($parentOrder);

                if ($inquiryResult) {
                    //If Payment Success then update status
                    if ($this->canUpdateToProcessing($inquiryResult)) {
                        $parentOrderId = (int) $parentOrder->getParentId();

                        if ($this->hotaiPayCacheLock->checkIsUpdateStatusAfterCheckoutSuccessLockNow($parentOrderId)) {
                            $lockValue = $this->hotaiPayCacheLock->getUpdateStatusAfterCheckoutSuccessLockValue($parentOrderId);
                            $this->hotaiPayCacheLock->writeLog("Found cache lock for update status after checkout success, current class: ".__CLASS__.", found lock value: ".$lockValue);
                        } else {
                            $this->hotaiPayCacheLock->writeLog("No cache lock for update status after checkout success, add cache lock by current process, current class: ".__CLASS__.", current process ID: ".getmypid());
                            $this->hotaiPayCacheLock->updateStatusAfterCheckoutSuccessLock($parentOrderId, __CLASS__."|".getmypid());
                            try {
                                $this->update->setStatusAfterCheckoutSuccess($parentOrderId);
                                $this->hotaiCoreCommon->writeLog(
                                    "Update ParentOrderId: " . $parentOrder->getIncrementId() . " to processing. ",
                                    self::LOG_PATH
                                );
                            } finally {
                                $this->hotaiPayCacheLock->updateStatusAfterCheckoutSuccessUnlock($parentOrderId);
                                $this->hotaiPayCacheLock->writeLog("Unlock cache lock for update status after checkout success, current class: ".__CLASS__.", current process ID: ".getmypid());
                            }
                        }

                        return ;
                    }

                }

                $this->hotaiCoreCommon->writeLog(
                    $parentOrder->getIncrementId(). " - Payment failed. There is nothing to update.",
                    self::LOG_PATH
                );
            }
        } catch (Exception $e) {
            $this->hotaiCoreCommon->writeLog(
                $e->getMessage(),
                self::LOG_PATH
            );

        }

    }

    /**
     * canUpdateToProcessing
     *
     * @param  array $statusArray
     * @return bool
     */
    public function canUpdateToProcessing($statusArray){

        $this->hotaiCoreCommon->writeLog(
            "[Inquiry Check Result] " . json_encode($statusArray, JSON_UNESCAPED_UNICODE),
            self::LOG_PATH
        );

        $validState = [
            CurrentState::AUTHORIZE_SUCCESS,
            CurrentState::PAYMENT_REQUEST,
            CurrentState::PAYMENT_REQUEST_PROCESSING,
            CurrentState::PAYMENT_REQUEST_SUCCESS,
            CurrentState::PAYMENT_REQUEST_FAIL,
        ];

        $stateCheck = isset($statusArray['CurrentState']) && in_array($statusArray['CurrentState'], $validState);

        return $stateCheck;

    }

}