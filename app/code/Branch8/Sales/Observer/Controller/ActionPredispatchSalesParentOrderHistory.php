<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Branch8\Sales\Observer\Controller;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\HotaiPay\Model\Payment\Update;
use Branch8\HotaiPay\Service\Api;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use \Magento\Customer\Model\Session;
use \Magento\Sales\Model\Order\Config;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\HotaiPoint\Helper\CacheLock as PointCacheLock;
use Branch8\HotaiPay\Helper\CacheLock as HotaiPayCacheLock;

class ActionPredispatchSalesParentOrderHistory implements \Magento\Framework\Event\ObserverInterface
{

    const LOG_PATH = 'Sales/recheckPendingPayment';

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory */
    protected $parentOrderCollectionFactory;

    /** @var \Magento\Customer\Model\Session*/
    protected $_customerSession;

    /** @var \Magento\Sales\Model\Order\Config*/
    protected $_orderConfig;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection*/
    protected $orders;

    /** @var \Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface $parentOrderManagement */
    private $parentOrderManagement;

    /** @var \Magento\Framework\App\Http\Context */
    protected $httpContext;

    protected $totalPage = 0;

    /** @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var mixed $hotaiShippingHelper */
    protected $hotaiShippingHelper;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $subOrderCollectionFactory */
    protected $subOrderCollectionFactory;

    /** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
    protected $resourceConnection;

    /** @var \Branch8\HotaiPay\Model\Payment\Update $update */
    protected $update;

    /** @var \Branch8\HotaiPay\Service\Api $api */
    protected $api;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var \Magento\Framework\Event\ManagerInterface $_eventManager */
    protected $_eventManager;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderRepository $parentOrderRepository */
    protected $parentOrderRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var PointCacheLock */
    protected $pointCacheLock;

    /** @var HotaiPayCacheLock */
    protected $hotaiPayCacheLock;

    protected $processId = "";

    public function __construct(
        CollectionFactory $parentOrderCollectionFactory,
        Session $customerSession,
        Config $orderConfig,
        ParentOrderManagementInterface $parentOrderManagement,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        OrderCollectionFactory $subOrderCollectionFactory,
        ResourceConnection $resourceConnection,
        Update $update,
        Api $api,
        HotaiCoreCommon $hotaiCoreCommon,
        EventManagerInterface $_eventManager,
        ParentOrderRepository $parentOrderRepository,
        OrderRepositoryInterface $orderRepository,
        PointCacheLock $pointCacheLock,
        HotaiPayCacheLock $hotaiPayCacheLock
    ) {
        $this->update                       = $update;
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->_customerSession             = $customerSession;
        $this->_orderConfig                 = $orderConfig;
        $this->parentOrderManagement        = $parentOrderManagement;
        $this->orderItemCollectionFactory   = $orderItemCollectionFactory;
        $this->subOrderCollectionFactory    = $subOrderCollectionFactory;
        $this->resourceConnection           = $resourceConnection;
        $this->api                          = $api;
        $this->hotaiCoreCommon              = $hotaiCoreCommon;
        $this->_eventManager                = $_eventManager;
        $this->parentOrderRepository        = $parentOrderRepository;
        $this->orderRepository              = $orderRepository;
        $this->pointCacheLock               = $pointCacheLock;
        $this->hotaiPayCacheLock            = $hotaiPayCacheLock;
        $this->processId                    = getmypid();
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        try {
            $customerId = $this->_customerSession->getCustomerId();

            if (! $customerId) {
                return false;
            }

            $collection = $this->parentOrderCollectionFactory->create()
                ->joinDetail(
                    [
                        '*',
                    ]
                )->addCustomerFilter(
                $this->_customerSession->getCustomerId()
            )->addFieldToFilter(
                'detail.status',
                HotaiStatus::STATUS_PENDING_PAYMENT
            )->addFieldToFilter(
                'detail.payment_method',
                \Branch8\HotaiPay\Model\Payment\HotaiPay::CODE
            );

            foreach ($collection as $parentOrder) {
                //If there is no increment id then return
                if (! $parentOrder->getIncrementId()) {
                    continue;
                }

                //log parent order id
                $this->hotaiCoreCommon->writeLog(
                    "--- Get ParentOrderId: " . $parentOrder->getIncrementId() . " ---",
                    self::LOG_PATH
                );

                // 重新圈存
                $this->rePreparePoint($parentOrder->getIncrementId());

                $checkPayment = $this->api->payment->inquiry(['orderId' => $parentOrder->getIncrementId()]);

                if ($checkPayment && $checkPayment['success'] == true) {

                    //If Payment Success then update status
                    if ($this->canUpdateToProcessing($checkPayment)) {
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

                        return;
                    }

                }

                $this->hotaiCoreCommon->writeLog(
                    "--- Payment failed. There is nothing to update. ---",
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
     * @param  array $checkPayment
     * @return bool
     */
    public function canUpdateToProcessing($checkPayment)
    {

        //成功交易
        if ($checkPayment['data']['Status'] == "0" && $checkPayment['data']['StatusDesc'] == "SUCCESS") {
            return true;
        }

        //該筆訂單編號已經做過交易，不接受重複交易
        if ($checkPayment['data']['Status'] == "10"
            && $checkPayment['data']['StatusCode'] == "E9998"
            && $checkPayment['data']['AuthAmt']) {
            return true;
        }

        return false;

    }

    /**
     * rePreparePoint 重新圈存
     *
     * @param  string $parentOrderIncrementId
     * @return void
     */
    protected function rePreparePoint($parentOrderIncrementId)
    {
        $parentOrder = $this->parentOrderRepository
            ->getByIncrementId((string) $parentOrderIncrementId);

        $subOrders = $parentOrder->getSuborderIds();

        foreach ($subOrders as $subOrder) {
            $order = $this->orderRepository->get($subOrder);
            if (!$this->pointCacheLock->checkIsPointReDeductionProcedureLockNow($order->getId())) {
                $pointCacheLockMessage = "Point re-deduction cache lock not found at ActionPredispatchSalesParentOrderHistory, add cache lock by current process, self process ID: {$this->processId}.";
                $this->hotaiCoreCommon->addSalesOrderHistoryComment($order, $pointCacheLockMessage, self::class);

                $this->pointCacheLock->pointReDeductionProcedureLock($order->getId(), $this->processId);
            } else {
                $lockId = $this->pointCacheLock->getPointReDeductionProcedureLockValue($order->getId());
                $pointCacheLockMessage = "Found point re-deduction cache lock at ActionPredispatchSalesParentOrderHistory, self process ID: {$this->processId}, locked process ID: {$lockId}.";
                $this->hotaiCoreCommon->addSalesOrderHistoryComment($order, $pointCacheLockMessage, self::class);
            }

            if ($this->checkIfNeedPointReDeductionProcedure($order)) {
                $this->_eventManager->dispatch('hotaiPay_payment_retry', [
                    "orderId" => (int) $subOrder,
                ]);
            }
        }
    }

    protected function checkIfNeedPointReDeductionProcedure($order): bool
    {
        $cond1 = $this->pointCacheLock->checkIsPointReDeductionProcedureLockNow($order->getId());

        $lockId = $this->pointCacheLock->getPointReDeductionProcedureLockValue($order->getId());
        $cond2 = $lockId == $this->processId;

        $pointCacheLockMessage = "";

        if ($cond1 && $cond2) {
            $pointCacheLockMessage = "Pass checkIfNeedPointReDeductionProcedure check at ActionPredispatchSalesParentOrderHistory, self process ID: {$this->processId}, locked process ID: {$lockId}.";
        } else {
            $pointCacheLockMessage = "Failed checkIfNeedPointReDeductionProcedure check at ActionPredispatchSalesParentOrderHistory, self process ID: {$this->processId}, locked process ID: {$lockId}.";
        }

        $this->hotaiCoreCommon->addSalesOrderHistoryComment($order, $pointCacheLockMessage, self::class);

        return $cond1 && $cond2;
    }
}
