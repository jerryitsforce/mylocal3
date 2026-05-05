<?php

namespace Branch8\HotaiPoint\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiPoint\Model\DeductionPointRetryStatus;
use Magento\Framework\Event\ManagerInterface;
use Branch8\HotaiCore\Model\Order\Status;

class DeductionRetry
{
    const LOG_FOLDER_NAME   = 'HotaiPoint/Cron/DeductionRetry';
    const RETRY_COUNT_LIMIT = 18;
    private const DEBUG_LOG_OPTION = LogOption::LOG_DEDUCTION_RETRY;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var ManagerInterface */
    protected $eventManager;

    protected $walkthroughLog;
    protected $commitFail          = false;
    protected $failCounterHitLimit = false;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        ManagerInterface $eventManager,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper
    ) {
        $this->hotaiCoreCommonHelper  = $hotaiCoreCommonHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository        = $orderRepository;
        $this->orderItemRepository    = $orderItemRepository;
        $this->eventManager           = $eventManager;
        $this->apiHelper              = $apiHelper;
        $this->commonHelper           = $commonHelper;

        $this->walkthroughLog = [];
    }

    public function execute()
    {
        $this->commonHelper->writeLogIfEnabled(
            "DeductionRetry cron start.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        try {
            $orderCollection = $this->getCommitRetryOrderCollection();

            foreach ($orderCollection as $order) {
                $this->commitFail          = false;
                $this->failCounterHitLimit = false;

                $this->handleOrderRetry($order);

                if ($this->failCounterHitLimit) {
                    $order->setData(
                        DeductionPointRetryStatus::FIELD_NAME,
                        DeductionPointRetryStatus::STATUS_RETRY_LIMIT_ERROR
                    );

                    $this->orderRepository->save($order);

                    continue;
                }

                /** @var Order $order */
                $order = $this->orderRepository->get($order->getId());

                $deductionTotalResult = $this->updateOrderFieldAfterAllDeductionProcess($order);

                $this->fireDeductioinEventIfNeeded($deductionTotalResult, $order);
            }
        } catch (\Throwable $th) {
            $this->commonHelper->writeLogIfEnabled(
                "Exception while executing cron flow, message: " . $th->getMessage(),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        }

        $this->commonHelper->writeLogIfEnabled(
            "DeductionRetry cron end.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    protected function getCommitRetryOrderCollection(): OrderCollection
    {
        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->addFieldToFilter(
            DeductionPointRetryStatus::FIELD_NAME,
            DeductionPointRetryStatus::STATUS_NEED_RETRY
        );

        return $orderCollection;
    }

    protected function handleOrderRetry(Order $order)
    {
        $logTitle = "Order ID: " . $order->getId() . ", ";
        $this->commonHelper->writeLogIfEnabled(
            $logTitle . "handleOrderRetry function start.",
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
            $this->walkthroughLog[] = "DeductionRetry cron handle start, order ID: " . $order->getId();
            $this->commonHelper->writeLogIfEnabled(
                $logTitle . "ready to handle order item(id: {$orderItem->getId()}).",
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            // 如果 quote id 沒有值就跳過
            if (!$order->getQuoteId()) {
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
    }

    protected function getOrderItemIdsString(Order $order): string
    {
        $orderItemIds = [];

        foreach ($order->getAllVisibleItems() as $orderItem) {
            $orderItemIds[] = $orderItem->getId();
        }

        return implode(",", $orderItemIds);
    }

    protected function checkIfPointUsedOnItem(OrderItem $orderItem): bool
    {
        return (bool) (float) $orderItem->getData("row_total_point_used");
    }

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

    protected function orderItemUpdateIfCommitSuccess(OrderItem $item, string $traceNo, string $message = ""): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "DeductionRetry cron - Deduction point commit success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $item->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $item->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);

        $this->orderItemRepository->save($item);
    }

    protected function orderItemUpdateIfFail(OrderItem $item, string $traceNo, string $message): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $item->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "DeductionRetry cron - Deduction point fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $currentFailCounter = $item->getData("hotai_point_deduction_point_commit_or_cancel_fail_counter");
        $newFailCounter     = $currentFailCounter + 1;

        if ($newFailCounter >= self::RETRY_COUNT_LIMIT) {
            $this->failCounterHitLimit = true;
        }

        $item->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $item->setData('hotai_point_deduction_point_commit_or_cancel_fail_counter', $newFailCounter);
        $item->setData('hotai_point_deduction_point_memo', $memoMessage);

        // $item->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL);

        $this->orderItemRepository->save($item);
    }

    protected function updateOrderFieldAfterAllDeductionProcess(Order $order): bool
    {
        $allSuccessFlag = true;

        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getData('hotai_point_deduction_point_progress_status') == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT) {
                continue;
            }

            if ($item->getData('hotai_point_deduction_point_progress_status') == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT) {
                continue;
            }

            if ($item->getData('hotai_point_deduction_point_progress_status') == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL) {
                continue;
            }

            $allSuccessFlag = false;
        }

        $completeStatus = ($allSuccessFlag) ? CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_DONE : CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_EXCEPTION_FAIL;

        $order->setData('hotai_point_deduction_point_complete', $completeStatus);

        if ($allSuccessFlag) {
            $order->setData(
                DeductionPointRetryStatus::FIELD_NAME,
                DeductionPointRetryStatus::STATUS_RETRY_SUCCESS
            );
        }

        $this->orderRepository->save($order);

        return $allSuccessFlag;
    }

    protected function fireDeductioinEventIfNeeded(bool $deductionTotalResult, Order $order): void
    {
        if (!$deductionTotalResult) {
            return;
        }
        if(!$order->getData('is_gift_order') || ($order->getData('is_gift_order') && $order->getData('is_gift_confirmed')) ){
            $this->eventManager->dispatch(CommonHelper::EVENT_DEDUCTION_COMMIT_FLOW_END, [
                "orderId" => $order->getId(),
                "quoteId" => $order->getQuoteId(),
            ]);
        }

        /** 贈禮訂單如果還沒有變成 processing 就不用分配票券 */
        if($order->getData('is_gift_order') && $order->getStatus() != Status::STATUS_PROCESSING) {
            return;
        }

        $this->eventManager->dispatch('ecpay_inovice_ticket_item_arrived_check', [
            "orderId" => $order->getId(),
        ]);
    }

    protected function checkIsTransactionLock(string $returnCode): bool
    {
        return $returnCode == $this->apiHelper::API_RESPONSE_CODE_TRANSACTION_LOCK;
    }
}
