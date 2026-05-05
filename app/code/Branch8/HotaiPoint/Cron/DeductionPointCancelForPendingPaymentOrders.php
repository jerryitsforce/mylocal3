<?php

namespace Branch8\HotaiPoint\Cron;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class DeductionPointCancelForPendingPaymentOrders
{
    const LOG_FOLDER_NAME              = 'Cron';
    const PENDING_PAYMENT_MINUTE_LIMIT = 10;
    private const LOG_SUBFOLDER_NAME = 'DeductionPointCancelForPendingPaymentOrders';
    private const DEBUG_LOG_OPTION = LogOption::LOG_DEDUCTION_POINT_CANCEL_FOR_PENDING_PAYMENT_ORDERS;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var Transaction */
    protected $transaction;

    protected $logFileName;
    protected $walkthroughLog;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        OrderItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        ManagerInterface $eventManager,
        Transaction $transaction
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->apiHelper              = $apiHelper;
        $this->commonHelper           = $commonHelper;
        $this->orderItemRepository    = $orderItemRepository;
        $this->orderRepository        = $orderRepository;
        $this->quoteRepository        = $quoteRepository;
        $this->eventManager           = $eventManager;
        $this->transaction            = $transaction;

        $this->logFileName    = "deduction_point_cancel_for_pending_payment_orders_" . date("Y_m_d") . ".log";
        $this->walkthroughLog = [];
    }

    /**
     * 排程執行入口
     *
     * @return void
     */
    public function execute()
    {
        $this->writeLog("DeductionPointCancelForPendingPaymentOrders cron start.");

        $orderCollection = $this->getPendingPaymentOrdersCollectionForDeductionPointCancel();

        $orders = $orderCollection->getItems();

        if (!$orderCollection->getSize()) {
            $this->writeLog("No order needs to be handled, DeductionPointCancelForPendingPaymentOrders cron end.");
            return;
        }

        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order->getId();
        }
        $this->writeLog("Order counts ready to be handled: " . $orderCollection->getSize());
        $this->writeLog("Order IDs: " . implode(',', $orderIds));

        foreach ($orders as $order) {
            $orderId  = $order->getId();
            $logTitle = "Order Id: {$orderId}, ";

            $atDefaultStatus = $this->checkDeductionFlowAtDefaultStatus($order);
            if (!$atDefaultStatus) {
                $status = $order->getData("hotai_point_deduction_point_complete");
                $this->writeLog($logTitle . "this order isn't at default status for deduction flow({$status}), continue.");
                continue;
            }

            // 同步所有 order items 的點數資料
            $this->writeLog($logTitle . "ready to syncHotaiPointFieldsForOrder.");
            $this->commonHelper->syncHotaiPointFieldsForOrder($order, self::LOG_FOLDER_NAME);

            foreach ($order->getAllVisibleItems() as $item) {
                $this->walkthroughLog = [];
                $traceNo              = "";

                try {
                    if (is_null($item->getData("row_total_point_used")) || $item->getData("row_total_point_used") <= 0) {
                        $this->writeLog($logTitle . "order item ID: {$item->getId()}, row_total_point_used: {$item->getData("row_total_point_used")}, skip.");
                        continue;
                    }

                    // 如果沒有點數圈存相關資料, 試圖從apiRecord裡面取得
                    if (!$this->commonHelper->checkDeductionPointDataAfterSync($item->getId())) {
                        $this->commonHelper->restoreDeductionPointDataFromApiRecordIfSyncIssue($item, $order);
                    }

                    if ($this->checkIfDeductionProgressAtFinalStatus($item)) {
                        $this->writeLog($logTitle . "order item ID: {$item->getId()}, current progress status: {$item->getData('hotai_point_deduction_point_progress_status')}, skip.");
                        continue;
                    }
                    $traceNo = $item->getData("hotai_point_deduction_point_trace_no");

                    if (empty($traceNo)) {
                        $exceptionMessage = $logTitle . "order item ID: {$item->getId()}, skip due to empty traceNo: {$traceNo}.";
                        $this->writeLog($exceptionMessage);

                        throw new \Exception($exceptionMessage);
                    }
                    $this->writeLog($logTitle . "order item ID: {$item->getId()}, ready to request Cancel API, traceNo: {$traceNo}");

                    $this->walkthroughLog[] = "Ready to request Cancel API.";
                    $cancelResponse         = $this->apiHelper->requestApiCancel($order->getCustomerId(), $traceNo);
                    $this->walkthroughLog[] = "Cancel API done, request data: " . $this->apiHelper->getRequestDataString();
                    $this->walkthroughLog[] = "Cancel API done, response data: " . json_encode($cancelResponse);

                    $this->orderItemUpdateIfSuccess($item);
                    $this->writeLog($logTitle . "order item ID: {$item->getId()}, handle success.");
                } catch (\Exception $e) {
                    $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
                    $this->writeLog($logTitle . "exception, order item ID: " . $item->getId() . ", message: " . $e->getMessage());
                    $this->orderItemUpdateIfFail($item, $e->getMessage());
                    $this->writeLog($logTitle . "order item ID: {$item->getId()}, handle fail.");
                }
            }

            $this->writeLog($logTitle . "ready to update hotai_point_deduction_point_complete depends on whether all order item done their deduction process.");
            $deductionTotalResult     = $this->updateOrderFieldAfterAllDeductionProcess($order);
            $deductionTotalResultText = ($deductionTotalResult) ? "true" : "false";
            $this->writeLog($logTitle . "update hotai_point_deduction_point_complete process done, deduction total result: {$deductionTotalResultText}");

            $this->fireDeductioinEventIfNeeded($deductionTotalResult, $order);
        }

        $this->writeLog("DeductionPointCancelForPendingPaymentOrders cron end.");
    }

    /**
     * 取得訂單狀態為"等待支付"但已超出規定時間的訂單, 以便後續進行解除圈存流程
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function getPendingPaymentOrdersCollectionForDeductionPointCancel()
    {
        $dateTimeLimit = date('Y-m-d H:i:s', strtotime("-" . self::PENDING_PAYMENT_MINUTE_LIMIT . " minutes"));

        $orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('status', ['in' => ['pending_payment', 'canceled']])
            ->addFieldToFilter('hotai_point_deduction_point_last_handle_time', ['lt' => $dateTimeLimit])
            ->addFieldToFilter('hotai_point_deduction_point_complete', 0)
            ->addFieldToFilter('is_paid', 0);

        return $orderCollection;
    }

    /**
     * 檢查order的整體兌點狀態是否處於"進行中"
     * 處於"進行中"狀態代表其他邏輯可能正在進行相關處理, 為避免衝突此時不能對此order進行後續流程
     *
     * @param Order $order
     * @return boolean
     */
    protected function checkDeductionFlowAtDefaultStatus(Order $order): bool
    {
        $status = $order->getData("hotai_point_deduction_point_complete");

        return $status == CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_DEFAULT;
    }

    /**
     * 判斷當前order item的兌點狀態是否達到最終狀態("已Commit"或"已Cancel")
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return boolean
     */
    protected function checkIfDeductionProgressAtFinalStatus(\Magento\Sales\Model\Order\Item $orderItem): bool
    {
        $progressStatus = $orderItem->getData("hotai_point_deduction_point_progress_status");

        if (!$this->checkIfPointUsedOnItem($orderItem)) {
            return true;
        }

        if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT) {
            return true;
        }

        if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL) {
            return true;
        }

        return false;
    }

    /**
     * 檢查sales order item是否有要使用點數
     *
     * @param OrderItem $orderItem
     * @return boolean
     */
    protected function checkIfPointUsedOnItem(\Magento\Sales\Model\Order\Item $orderItem): bool
    {
        return (bool) (float) $orderItem->getData("row_total_point_used");
    }

    /**
     * 若order item的兌點cancel流程成功時對order item的資料庫欄位進行更新
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $traceNo
     * @param string $message
     * @return void
     */
    protected function orderItemUpdateIfSuccess(\Magento\Sales\Model\Order\Item $item, string $message = ""): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point cancel for pending payment cron success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $item->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);
        $this->orderItemRepository->save($item);
    }

    /**
     * 兌點中途發生例外後對資料庫進行寫入
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $message
     * @return void
     */
    protected function orderItemUpdateIfFail(\Magento\Sales\Model\Order\Item $item, string $message): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point cancel for pending payment cron fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $currentFailCounter = $item->getData("hotai_point_deduction_point_commit_or_cancel_fail_counter");
        $newFailCounter     = $currentFailCounter + 1;

        $item->setData('hotai_point_deduction_point_commit_or_cancel_fail_counter', $newFailCounter);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);

        $item->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL);

        $this->orderItemRepository->save($item);
    }

    /**
     * 檢查一遍該order底下的所有order item,
     * 若是全部都order item都不需要再進行點數處理則將order的hotai_point_deduction_point_complete欄位更新
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function updateOrderFieldAfterAllDeductionProcess(\Magento\Sales\Model\Order $order): bool
    {
        $allSuccessFlag = true;

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->checkIfPointUsedOnItem($item)) {
                continue;
            }

            if ($this->checkIfDeductionProgressAtFinalStatus($item)) {
                continue;
            }

            $allSuccessFlag = false;
        }

        $completeStatus = ($allSuccessFlag) ? CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_DONE : CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_EXCEPTION_FAIL;

        $order->setData('hotai_point_deduction_point_complete', $completeStatus);
        $this->orderRepository->save($order);

        return $allSuccessFlag;
    }

    /**
     * 在確認所有order item都完成點數流程後($deductionTotalResult),
     * 根據支付成功與否($paymentSuccess)發送點數相關事件
     *
     * @param boolean $deductionTotalResult
     * @param boolean $paymentSuccess
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    protected function fireDeductioinEventIfNeeded(bool $deductionTotalResult, \Magento\Sales\Model\Order $order): void
    {
        if (!$deductionTotalResult) {
            return;
        }

        $this->eventManager->dispatch(CommonHelper::EVENT_DEDUCTION_CANCEL_FLOW_END, [
            "orderId" => $order->getId(),
            "quoteId" => $order->getQuoteId(),
        ]);
    }

    /**
     * 寫入log
     *
     * @param string $message
     * @return void
     */
    protected function writeLog(string $message): void
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }
}
