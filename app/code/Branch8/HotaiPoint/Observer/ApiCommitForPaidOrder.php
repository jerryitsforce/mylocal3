<?php

namespace Branch8\HotaiPoint\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiPoint\Model\DeductionPointRetryStatus;

class ApiCommitForPaidOrder implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Observer/ApiCommitForPaidOrder';
    private const DEBUG_LOG_OPTION = LogOption::LOG_API_COMMIT_FOR_PAID_ORDER;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var ManagerInterface */
    protected $eventManager;

    protected $logFileName;
    protected $walkthroughLog;
    protected $commitFail = false;
    protected $logTitle = "";

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        OrderRepositoryInterface $orderRepository,
        QuoteRepository $quoteRepository,
        OrderItemRepository $orderItemRepository,
        ManagerInterface $eventManager
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->apiHelper             = $apiHelper;
        $this->commonHelper          = $commonHelper;
        $this->orderRepository       = $orderRepository;
        $this->quoteRepository       = $quoteRepository;
        $this->orderItemRepository   = $orderItemRepository;
        $this->eventManager          = $eventManager;
    }

    public function execute(Observer $observer)
    {
        try {
            $logTitle = "";
            $data     = $observer->getEvent()->getData();
            $orderId  = $data["orderId"] ?? "";

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);

            $logTitle = "Order ID: " . $order->getId() . ", ";
            $this->logTitle = $logTitle;
            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "ApiCommitForPaidOrder observer start.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $order->setData('hotai_point_deduction_point_last_handle_time', date('Y-m-d H:i:s'));
            $this->orderRepository->save($order);

            $orderItemIdsString = $this->getOrderItemIdsString($order);
            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "ready to handle order items(ids: {$orderItemIdsString}).",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            // 同步所有 order items 的點數資料
            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "ready to syncHotaiPointFieldsForOrder.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
            $this->commonHelper->syncHotaiPointFieldsForOrder($order, self::LOG_FOLDER_NAME);

            foreach ($order->getAllVisibleItems() as $orderItem) {
                $this->walkthroughLog   = [];
                $this->walkthroughLog[] = "ApiCommitForPaidOrder order item handle start, order ID: " . $order->getId() . ", order item ID: " . $orderItem->getId();
                $this->commonHelper->writeLogIfEnabled(
                    $logTitle . "ready to handle order item(id: {$orderItem->getId()}).",
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );

                if (!$order->getQuoteId()) {
                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "order item(id: {$orderItem->getId()}) skip due to quote id is empty.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                    continue;
                }

                // 檢查 row_total_point_used 有值才繼續
                if (!$this->checkIfPointUsedOnItem($orderItem)) {
                    $rowTotalPointUsed = $orderItem->getData("row_total_point_used");
                    $rowTotalPointUsed = is_null($rowTotalPointUsed) ? "null" : $rowTotalPointUsed;

                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "order item(id: {$orderItem->getId()}) skip due to row_total_point_used value: {$rowTotalPointUsed}.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );

                    continue;
                }

                // 如果沒有點數圈存相關資料, 試圖從apiRecord裡面取得
                if (!$this->commonHelper->checkDeductionPointDataAfterSync($orderItem->getId())) {
                    $this->commonHelper->restoreDeductionPointDataFromApiRecordIfSyncIssue($orderItem, $order);
                }

                // 檢查流程狀態不屬於"已Commit"或"已Cancel"才繼續
                if ($this->checkIfDeductionProgressAtFinalStatus($orderItem)) {
                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "order item(id: {$orderItem->getId()}) skip due to progress_status value: {$orderItem->getData("hotai_point_deduction_point_progress_status")}.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                    continue;
                }

                $traceNo = $orderItem->getData("hotai_point_deduction_point_trace_no");

                if (!$traceNo) {
                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "order item(id: {$orderItem->getId()}) skip due to hotai_point_deduction_point_trace_no value is NULL.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                    continue;
                }

                try {
                    // 和泰點數commit流程
                    $this->walkthroughLog[] = "Ready to request Commit API.";
                    $commitResponse         = $this->apiHelper->requestApiCommit(
                        $order->getCustomerId(),
                        $traceNo,
                        [
                            $this->apiHelper::API_RESPONSE_CODE_SUCCESS,
                            $this->apiHelper::API_RESPONSE_CODE_TRANSACTION_LOCK
                        ]
                    );
                    $this->walkthroughLog[] = "Commit API done, request data: " . $this->apiHelper->getRequestDataString();
                    $this->walkthroughLog[] = "Commit API done, response data: " . json_encode($commitResponse);

                    // 判斷commitResponse是否為transaction lock
                    $returnCode = $this->apiHelper->getReturnCodeFromResponse($commitResponse);
                    if ($this->checkIsTransactionLock($returnCode)) {
                        $this->walkthroughLog[] = "Commit API result is transaction lock, extra handle start.";

                        // 如果是lock則請求解除API
                        $this->walkthroughLog[] = "Ready to request Unlock API.";
                        $unlockResponse         = $this->apiHelper->requestApiUnlockOneId($order->getCustomerId());
                        $this->walkthroughLog[] = "Unlock API done, request data: " . $this->apiHelper->getRequestDataString();
                        $this->walkthroughLog[] = "Unlock API done, response data: " . json_encode($unlockResponse);

                        // 然後再次commit, 這次預期一定要成功, 所以不傳遞額外allow return code array
                        $this->walkthroughLog[] = "Ready to request Commit API again after Unlock API request is done.";
                        $commitAgainResponse    = $this->apiHelper->requestApiCommit(
                            $order->getCustomerId(),
                            $traceNo
                        );
                        $this->walkthroughLog[] = "Commit API again done, request data: " . $this->apiHelper->getRequestDataString();
                        $this->walkthroughLog[] = "Commit API again done, response data: " . json_encode($commitAgainResponse);
                    }

                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "order item(id: {$orderItem->getId()}) handled by commit flow done.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );

                    $this->orderItemUpdateIfCommitSuccess($orderItem, $traceNo);

                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "order item(id: {$orderItem->getId()}) orderItemUpdateIfCommitSuccess done.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                } catch (\Exception $e) {
                    $this->commitFail = true;

                    $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
                    $this->commonHelper->writeLogIfEnabled($logTitle . "exception data: " . json_encode([
                        "Title"            => $logTitle,
                        "OrderItemId"      => $orderItem->getId(),
                        "ExceptionMessage" => $e->getMessage(),
                        "WalkthroughLog"   => $this->walkthroughLog,
                    ]), self::LOG_FOLDER_NAME, self::DEBUG_LOG_OPTION);
                    $this->orderItemUpdateIfFail($orderItem, $traceNo, $e->getMessage());
                }
            }

            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "order item loop done.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);

            $deductionTotalResult = $this->updateOrderFieldAfterAllDeductionProcess($order);

            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "updateOrderFieldAfterAllDeductionProcess done.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            $this->fireDeductioinEventIfNeeded($deductionTotalResult, $order);

            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "fireDeductioinEventIfNeeded done.",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        } catch (\Throwable $th) {
            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "Outer exception, message: " . $th->getMessage(),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            throw new \Exception($th->getMessage());
        }

        $this->commonHelper->writeLogIfEnabled(
            $logTitle . "ApiCommitForPaidOrder observer end.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    /**
     * 將sales order裡包含的所有product id整理成字串
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    protected function getOrderItemIdsString(\Magento\Sales\Model\Order $order): string
    {
        $orderItemIds = [];

        foreach ($order->getAllVisibleItems() as $orderItem) {
            $orderItemIds[] = $orderItem->getId();
        }

        return implode(",", $orderItemIds);
    }

    /**
     * 檢查sales order item是否有要使用點數
     *
     * @param OrderItem $orderItem
     * @return boolean
     */
    protected function checkIfPointUsedOnItem(OrderItem $orderItem): bool
    {
        return (bool) (float) $orderItem->getData("row_total_point_used");
    }

    /**
     * 確認sales order item目前的兌點流程是否已執行完畢需要跳過
     *
     * @param OrderItem $orderItem
     * @return boolean
     */
    protected function checkIfDeductionProgressAtFinalStatus(OrderItem $orderItem): bool
    {
        $currentStatus = $orderItem->getData("hotai_point_deduction_point_progress_status");

        if ($currentStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT) {
            return true;
        }

        if ($currentStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL) {
            return true;
        }

        return false;
    }

    /**
     * 兌點Commit成功後對資料庫進行寫入
     *
     * @param OrderItem $item
     * @param string $traceNo
     * @param string $message
     * @return void
     */
    protected function orderItemUpdateIfCommitSuccess(OrderItem $item, string $traceNo, string $message = ""): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point commit success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $item->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $item->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);

        $this->orderItemRepository->save($item);
    }

    /**
     * 兌點中途發生例外後對資料庫進行寫入
     *
     * @param OrderItem $item
     * @param string $traceNo
     * @param string $message
     * @return void
     */
    protected function orderItemUpdateIfFail(OrderItem $item, string $traceNo, string $message): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $currentFailCounter = $item->getData("hotai_point_deduction_point_commit_or_cancel_fail_counter");
        $newFailCounter     = $currentFailCounter + 1;

        $item->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $item->setData('hotai_point_deduction_point_commit_or_cancel_fail_counter', $newFailCounter);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);

        // 為了交給retry cron之後能從原先的流程狀態繼續下去 Branch8\HotaiPoint\Cron\DeductionRetry
        // sales_order_item點數commit錯誤後不要修改流程狀態
        // $item->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL);

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
        $itemData = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $progressStatus = $item->getData('hotai_point_deduction_point_progress_status');

            $itemData[] = [
                "ItemId" => $item->getId(),
                "hotai_point_deduction_point_progress_status" => $progressStatus,
            ];

            if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT) {
                continue;
            }

            if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT) {
                continue;
            }

            if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL) {
                continue;
            }

            $allSuccessFlag = false;
        }

        $completeStatus = ($allSuccessFlag) ? CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_DONE : CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_EXCEPTION_FAIL;

        $order->setData('hotai_point_deduction_point_complete', $completeStatus);

        if ($this->commitFail) {
            $order->setData(
                DeductionPointRetryStatus::FIELD_NAME,
                DeductionPointRetryStatus::STATUS_NEED_RETRY
            );
        }

        $this->commonHelper->writeLogIfEnabled(
            $this->logTitle . "updateOrderFieldAfterAllDeductionProcess function ready to save, data: " . json_encode(
                [
                    "AllSuccessFlag" => $allSuccessFlag,
                    "CompleteStatus" => $completeStatus,
                    "CommitFail" => $this->commitFail,
                    "ItemData" => $itemData,
                ]
            ),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

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
        $this->commonHelper->writeLogIfEnabled(
            $this->logTitle . "enter fireDeductioinEventIfNeeded function, data: " . json_encode(
                [
                    "DeductionTotalResult" => $deductionTotalResult,
                    "IsGiftOrder" => $order->getData('is_gift_order'),
                    "IsGiftConfirmed" => $order->getData('is_gift_confirmed'),
                ]
            ),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        if (!$deductionTotalResult) {
            return;
        }

        if (!$order->getData('is_gift_order') || ($order->getData('is_gift_order') && $order->getData('is_gift_confirmed'))) {
            $this->eventManager->dispatch(CommonHelper::EVENT_DEDUCTION_COMMIT_FLOW_END, [
                "orderId" => $order->getId(),
                "quoteId" => $order->getQuoteId(),
            ]);
        }
    }

    protected function checkIsTransactionLock(string $returnCode): bool
    {
        return $returnCode == $this->apiHelper::API_RESPONSE_CODE_TRANSACTION_LOCK;
    }
}
