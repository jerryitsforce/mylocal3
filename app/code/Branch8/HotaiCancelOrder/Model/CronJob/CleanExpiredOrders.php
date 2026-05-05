<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types=1);

namespace Branch8\HotaiCancelOrder\Model\CronJob;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Carbon\Carbon;
use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoresConfig;
use Webkul\Mpsplitorder\Model\MpsplitorderFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory as ParentOrderDetailCollectionFactory;
use Zend_Log_Exception;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCancelOrder\Helper\Common as HotaiCancelOrderCommonHelper;
use Branch8\HotaiCancelOrder\Model\Config\Source\LogOption;

/**
 * Class that provides functionality of cleaning expired quotes by cron
 */
class CleanExpiredOrders
{
    const FILTER_ORDER_STATUS = [
        OrderStatus::STATUS_PENDING,
        OrderStatus::STATUS_PENDING_PAYMENT,
        OrderStatus::STATUS_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE
    ];

    const HOTAI_POINT_API_FAIL_LIMIT = 3;
    const LOG_FOLDER_NAME            = 'hotai_auth/Cron';
    private const DEBUG_LOG_OPTION   = LogOption::LOG_CLEAN_EXPIRED_ORDERS;

    /**
     * @var StoresConfig
     */
    protected $storesConfig;

    /**
     * @var MpsplitorderFactory
     */
    protected $mpsplitorderFactory;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var CommonHelper
     */
    private $commonHelper;

    /**
     * @var HotaiCancelOrderCommonHelper
     */
    private HotaiCancelOrderCommonHelper $hotaiCancelOrderCommonHelper;
    private $walkthroughLog;

    /** @var ParentOrderDetailCollectionFactory */
    protected $parentOrderDetailCollectionFactory;


    protected $parentOrderManagementInterface;

    protected $parentOrderRepository;
    public $scopeConfig;

    protected $_timeZone;
    protected $registry;

    /**
     * @param StoresConfig $storesConfig
     * @param CollectionFactory $collectionFactory
     * @param MpsplitorderFactory $mpsplitorderFactory
     * @param OrderManagementInterface|null $orderManagement
     * @param OrderRepository $orderRepository
     * @param OrderItemRepository $orderItemRepository
     * @param ApiHelper $apiHelper
     * @param CommonHelper $commonHelper
     * @param ParentOrderDetailCollectionFactory $parentOrderDetailCollectionFactory
     * @param HotaiCancelOrderCommonHelper $hotaiCancelOrderCommonHelper
     */
    public function __construct(
        StoresConfig $storesConfig,
        CollectionFactory $collectionFactory,
        MpsplitorderFactory $mpsplitorderFactory,
        OrderManagementInterface $orderManagement = null,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        ParentOrderDetailCollectionFactory $parentOrderDetailCollectionFactory,
        HotaiCancelOrderCommonHelper $hotaiCancelOrderCommonHelper,
        ParentOrderManagementInterface $parentOrderManagementInterface,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Magento\Framework\Registry $registry
    ) {
        $this->registry                           = $registry;
        $this->storesConfig                       = $storesConfig;
        $this->orderCollectionFactory             = $collectionFactory;
        $this->mpsplitorderFactory                = $mpsplitorderFactory;
        $this->orderManagement                    = $orderManagement ?: ObjectManager::getInstance()->get(
            OrderManagementInterface::class
        );
        $this->orderRepository                    = $orderRepository;
        $this->orderItemRepository                = $orderItemRepository;
        $this->apiHelper                          = $apiHelper;
        $this->commonHelper                       = $commonHelper;
        $this->walkthroughLog                     = [];
        $this->parentOrderDetailCollectionFactory = $parentOrderDetailCollectionFactory;
        $this->hotaiCancelOrderCommonHelper       = $hotaiCancelOrderCommonHelper;
        $this->parentOrderManagementInterface     = $parentOrderManagementInterface;
        $this->parentOrderRepository              = $parentOrderRepository;
        $this->scopeConfig                        = $scopeConfig;
        $this->_timeZone                          = $timeZone;
    }

    /**
     * Clean expired quotes (cron process)
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function execute()
    {
        // $lifetimes = $this->storesConfig->getStoresConfigByPath('sales/orders/delete_pending_after');
        $lifetime = $this->scopeConfig->getValue(
            'sales/orders/delete_pending_after',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        /** Too many object in ori $lifetimes */
        // foreach ($lifetimes as $storeId => $lifetime) {

        /** 先跑 store_id = 0 (default) 的單，避免多做*/
        // if ($storeId == 1) {
        //     continue;
        // }

        $this->writeCleanExpireOrderCronLog("lifetime: $lifetime");

        /** 母訂單 目前沒有StoreId之分 */
        $parentOrders = $this->parentOrderDetailCollectionFactory->create();
        $parentOrders->addFieldToFilter('status', ['in' => self::FILTER_ORDER_STATUS]);

        //Rule get order of 30 minutes ago
        $thirtyMinutesAgo = $this->_timeZone->date()->modify('-30 minutes');
        $currentTime      = $this->_timeZone->convertConfigTimeToUtc($thirtyMinutesAgo);

        $parentOrders->addFieldToFilter('created_at', ['lteq' => $currentTime]);

        foreach ($parentOrders as $parentOrderDetail) {
            $this->writeCleanExpireOrderCronLog("Parent Orders Id: " . $parentOrderDetail->getParentId());
            $this->writeCleanExpireOrderCronLog("Parent Orders UpdateAt: " . $parentOrderDetail->getUpdatedAt());

            /** Load parent id */
            $mainParentOrder = $this->mpsplitorderFactory->create()->load(
                $parentOrderDetail->getParentId(),
                'index_id'
            );
            $childOrderIds   = explode(',', $mainParentOrder->getOrderIds());
            /** 定義母訂單狀態為true */
            $parentOrderIdStatus = true;

            // 將母訂單底下子訂單一次取出
            $childOrders = [];
            $getChildOrdersSuccess = true;
            foreach ($childOrderIds as $entityId) {
                try {
                    $childOrders[] = $this->orderRepository->get((int)$entityId);
                } catch (Exception $e) {
                    $this->writeCleanExpireOrderCronLog(
                        "Exception during get child order, ID: " . $entityId . ", message: " . $e->getMessage()
                    );
                    $getChildOrdersSuccess = false;
                    continue;
                }
            }

            if (!$getChildOrdersSuccess) {
                $this->writeCleanExpireOrderCronLog("Failed to retrieve all child orders for parent ID: " . $parentOrderDetail->getParentId().", skip this parent order handle.");
                continue;
            }

            // 檢查母訂單底下的子訂單是否有開立發票
            $childOrderInvoiced = false;
            $invoicedChildOrderIds = [];
            foreach ($childOrders as $order) {
                if ($order->getData("ecpay_invoice_tag") == 1) {
                    $childOrderInvoiced = true;
                    $invoicedChildOrderIds[] = $order->getId();
                }
            }

            // 如果有開立發票的子訂單, 則跳過取消流程
            if ($childOrderInvoiced) {
                $message = "Parent Orders Id: " . $parentOrderDetail->getParentId();
                $message .= ", has invoiced sub order so skip";
                $message .= ", invoiced order IDs: " . implode(',', $invoicedChildOrderIds);
                $this->writeCleanExpireOrderCronLog($message);
                continue;
            }

            foreach ($childOrders as $order) {
                $entityId = $order->getId();
                /** 釋放圈存 如果圈存是在母訂單 就放到最上面做*/
                try {
                    // $order = $this->orderRepository->get($entityId);

                    if ($order->getStatus() === Order::STATE_CLOSED) {
                        continue;
                    }
                    /** 子訂單 判斷是否需要執行點數 */
                    if ($order->getPointUsedTotal() > 0) {
                        $this->writeCleanExpireOrderCronLog(
                            "子訂單 ID: " . $entityId . " 有使用點數:" . $order->getPointUsedTotal()
                        );
                        $this->cancelDeductionPointByOrder($order);
                        $this->updateOrderFieldIfAllItemSuccess($order);

                        if ($this->checkIfDeductionPointFlowCompleteByOrder($order)) {
                            $this->writeCleanExpireOrderCronLog("取消點數成功");
                            // $this->orderManagement->cancel((int) $entityId);
                        } else {
                            /** 如果有一個圈存失敗 就設定為false 不更改母訂單 */
                            $this->writeCleanExpireOrderCronLog("取消點數失敗");
                            $parentOrderIdStatus = false;
                        }
                    } else {
                        $this->writeCleanExpireOrderCronLog("直接取消訂單");
                        // $this->orderManagement->cancel((int) $entityId);
                    }
                } catch (Exception $e) {
                    $this->writeCleanExpireOrderCronLog(
                        "Exception, order item ID: " . $entityId . ", message: " . $e->getMessage()
                    );
                    $parentOrderIdStatus = false;
                    continue;
                }
            }

            /** 確認子訂單沒問題後 母訂單關閉 */
            if ($parentOrderIdStatus) {
                $this->writeCleanExpireOrderCronLog(
                    "OLD STATUS =====> " . $parentOrderDetail->getParentId() . '=' . $parentOrderDetail->getStatus()
                );
                // $parentOrderDetail->setStatus(Order::STATE_CANCELED);
                // $parentOrderDetail->setState(Order::STATE_CANCELED);
                $parentOrderDetail->setIsAutoCancelled(1);
                $parentOrderDetail->save();

                try {
                    /** 從母訂單層級 Cancel，連同子訂單也一起 Cancel */
                    $parentOrder = $this->parentOrderRepository->get((int)$parentOrderDetail->getParentId());
                    $this->registry->register('parent_order_auto_cancel', true);
                    $this->parentOrderManagementInterface->cancel($parentOrder);
                    if ($this->registry->registry('parent_order_auto_cancel')) {
                        $this->registry->unregister('parent_order_auto_cancel');
                    }

                    /** 更新孫訂單的 item 為取消狀態 */
                    $this->updateItemFlowStatus($childOrderIds);
                } catch (Exception $e) {
                    $this->writeCleanExpireOrderCronLog($e->getMessage());
                }

                $this->writeCleanExpireOrderCronLog(
                    "DONE =====> Parent Orders Id: " . $parentOrderDetail->getParentId(
                    ) . '===Child==' . $mainParentOrder->getOrderIds()
                );
            }
        }
        // }
    }

    /**
     * 依據傳入的sales_order物件去cancel底下的sales_order_item的和泰點數圈存
     * @param Order $order
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function cancelDeductionPointByOrder(\Magento\Sales\Model\Order $order): void
    {
        $logTitle = "Order ID: " . $order->getId() . ", ";
        $this->writeCleanExpireOrderCronLog($logTitle . "ready to syncHotaiPointFieldsForOrder.");
        try {
            $this->commonHelper->syncHotaiPointFieldsForOrder($order, self::LOG_FOLDER_NAME);
        } catch (Exception $e) {
            $this->writeCleanExpireOrderCronLog($logTitle . "syncHotaiPointFieldsForOrder failed, message: " . $e->getMessage());
        }

        $items = $order->getAllVisibleItems();

        foreach ($items as $item) {
            $this->walkthroughLog = [];

            if (!$this->checkDeductionPointProgressToContinue($item)) {
                $this->writeCleanExpireOrderCronLog(
                    "Order item ID: {$item->getId()}, current progress status: {$item->getData('hotai_point_deduction_point_progress_status')}, skip."
                );
                continue;
            }

            try {
                $traceNo = $item->getData("hotai_point_deduction_point_trace_no");

                $this->writeCleanExpireOrderCronLog(
                    "Order item ID: {$item->getId()}, ready to request Cancel API, traceNo: {$traceNo}"
                );

                $this->walkthroughLog[] = "Ready to request Cancel API.";
                $cancelResponse         = $this->apiHelper->requestApiCancel((int)$order->getCustomerId(), $traceNo);
                $this->walkthroughLog[] = "Cancel API done, request data: " . $this->apiHelper->getRequestDataString();
                $this->walkthroughLog[] = "Cancel API done, response data: " . json_encode($cancelResponse);

                $this->orderItemUpdateIfSuccess($item, $traceNo);
                $this->writeCleanExpireOrderCronLog("Order item ID: {$item->getId()}, handle success.");
            } catch (Exception $e) {
                $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString(
                    );
                $this->writeCleanExpireOrderCronLog(
                    "Exception, order item ID: " . $item->getId() . ", message: " . $e->getMessage()
                );
                $this->orderItemUpdateIfFail($item, $traceNo, $e->getMessage());
                $this->writeCleanExpireOrderCronLog("Order item ID: {$item->getId()}, handle fail.");
            }
        }
    }

    /**
     * 判斷當前order item的兌點狀態是否符合略過條件
     *
     * @param Item $item
     * @return bool
     */
    private function checkDeductionPointProgressToContinue(Item $item): bool
    {
        $status = $item->getData("hotai_point_deduction_point_progress_status");

        $cond1 = $status == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_MADE;
        $cond2 = $status == CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_EXCEPTION_FAIL && !empty($item->getData("hotai_point_deduction_point_trace_no"));

        return $cond1 || $cond2;
    }

    /**
     * 若order item的兌點cancel流程成功時對order item的資料庫欄位進行更新
     *
     * @param Item $item
     * @param string $traceNo
     * @param string $message
     * @return void
     */
    private function orderItemUpdateIfSuccess(
        Item $item,
        string $traceNo,
        string $message = ""
    ): void {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point cancel success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $item->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $item->setData(
            'hotai_point_deduction_point_progress_status',
            CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL
        );
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);
        $this->orderItemRepository->save($item);
    }

    /**
     * 若order item的兌點cancel流程失敗時對order item的資料庫欄位進行更新
     * 每次失敗會將失敗次數記錄在hotai_point_deduction_point_commit_or_cancel_fail_counter
     * 當次數達到設定上限時會將兌點狀態欄位(hotai_point_deduction_point_progress_status)改為-1
     * 之後排程不會再針對此order item進行API請求
     *
     * @param Item $item
     * @param string $traceNo
     * @param string $message
     * @return void
     */
    private function orderItemUpdateIfFail(
        Item $item,
        string $traceNo,
        string $message
    ): void {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point cancel fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $currentFailCounter = $item->getData("hotai_point_deduction_point_commit_or_cancel_fail_counter");
        $newFailCounter     = $currentFailCounter + 1;

        $item->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $item->setData('hotai_point_deduction_point_commit_or_cancel_fail_counter', $newFailCounter);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);

        if (self::HOTAI_POINT_API_FAIL_LIMIT <= $newFailCounter) {
            $item->setData(
                'hotai_point_deduction_point_progress_status',
                CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL
            );
        }

        $this->orderItemRepository->save($item);
    }

    /**
     * order底下的order item都跑完cancel流程後檢查一遍該order底下的所有order item,
     * 若是全部都完成兌點commit或cancel則將hotai_point_deduction_point_complete設為1視為兌點處理完成
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function updateOrderFieldIfAllItemSuccess(\Magento\Sales\Model\Order $order): void
    {
        $allSuccessFlag = true;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get((int)$order->getId());

        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getData('hotai_point_deduction_point_progress_status') == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT) {
                continue;
            }

            if ($item->getData(
                    'hotai_point_deduction_point_progress_status'
                ) == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT) {
                continue;
            }

            if ($item->getData(
                    'hotai_point_deduction_point_progress_status'
                ) == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL) {
                continue;
            }

            $allSuccessFlag = false;
        }

        if ($allSuccessFlag) {
            $order->setData('hotai_point_deduction_point_complete', 1);
            $this->orderRepository->save($order);
        }
    }

    /**
     * 根據sales_order的hotai_point_deduction_point_complete欄位判斷底下的item是否都完成兌點流程
     *
     * @param \Magento\Sales\Model\Order $order
     * @return integer
     */
    private function checkIfDeductionPointFlowCompleteByOrder(\Magento\Sales\Model\Order $order): int
    {
        return (int)$order->getData("hotai_point_deduction_point_complete");
    }

    /**
     * 寫入log
     * @param string $message
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeCleanExpireOrderCronLog(string $message): void
    {
        $this->hotaiCancelOrderCommonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    /**
     * updateItemFlowStatus
     *
     * @param array $childOrderIds
     * @return void
     */
    private function updateItemFlowStatus($childOrderIds)
    {
        foreach ($childOrderIds as $entityId) {
            $order = $this->orderRepository->get($entityId);
            /** 更新孫訂單的 item 為取消狀態 */
            foreach ($order->getAllItems() as $item) {
                $item->setFlowStatus(Order::STATE_CANCELED);
                $item->save();
            }
        }
    }
}
