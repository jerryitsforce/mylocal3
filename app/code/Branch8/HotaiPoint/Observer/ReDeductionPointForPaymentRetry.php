<?php

namespace Branch8\HotaiPoint\Observer;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiPoint\Helper\ApiFlow as ApiFlowHelper;

class ReDeductionPointForPaymentRetry implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Observer';
    private const LOG_SUBFOLDER_NAME = 'ReDeductionPointForPaymentRetryObserver';
    private const DEBUG_LOG_OPTION = LogOption::LOG_RE_DEDUCTION_POINT_FOR_PAYMENT_RETRY;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var CartInterfaceFactory */
    protected $quoteItemFactory;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Transaction */
    protected $transaction;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var ApiFlowHelper */
    protected $apiFlowHelper;

    protected $logFileName;
    protected $walkthroughLog;

    public function __construct(
        QuoteRepository $quoteRepository,
        OrderItemRepository $orderItemRepository,
        CartInterfaceFactory $quoteItemFactory,
        OrderRepositoryInterface $orderRepository,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        Transaction $transaction,
        ManagerInterface $eventManager,
        ApiFlowHelper $apiFlowHelper
    ) {
        $this->quoteRepository     = $quoteRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->quoteItemFactory    = $quoteItemFactory;
        $this->orderRepository     = $orderRepository;
        $this->apiHelper           = $apiHelper;
        $this->commonHelper        = $commonHelper;
        $this->transaction         = $transaction;
        $this->eventManager        = $eventManager;
        $this->apiFlowHelper       = $apiFlowHelper;

        $this->logFileName = "re_deduction_point_for_payment_retry_observer_" . date("Y_m_d") . ".log";
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data    = $observer->getEvent()->getData();
        $orderId = $data["orderId"];
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        $this->commonHelper->updateDeductionPointLastHandleTime($order->getId());

        $logTitle = "Order Id: {$orderId}, ";
        $this->writeLog($logTitle . "re-deduction point for payment retry observer start, received data: " . json_encode($data, JSON_UNESCAPED_UNICODE));

        $isProcessing = $this->checkIfOrderProcessingDeductionFlow($order);
        if ($isProcessing) {
            $this->writeLog($logTitle . "this order is in processing status for deduction flow, observer ends.");
            throw new \Exception("This order is in processing status for deduction flow.");
        }

        // 同步所有 order items 的點數資料
        $this->writeLog($logTitle . "ready to syncHotaiPointFieldsForOrder.");
        $this->commonHelper->syncHotaiPointFieldsForOrder($order, self::LOG_FOLDER_NAME);

        foreach ($order->getAllVisibleItems() as $orderItem) {
            $apiFlowResult = [];
            $itemLogTitle           = $logTitle . "order item Id: {$orderItem->getId()}, ";
            $this->walkthroughLog   = [];
            $this->walkthroughLog[] = "ReDeductionPointForPaymentRetry observer handle start, observer received data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->writeLog($itemLogTitle . "ready to handle order item.");

            $requestPoint = $orderItem->getData("row_total_point_used");
            if (empty($requestPoint)) {
                $this->writeLog($itemLogTitle . "empty request point: {$requestPoint}, continue.");
                continue;
            }

            $needReDeduction = $this->checkIfOrderItemNeedReDeductionByStatus($orderItem);
            if (!$needReDeduction) {
                $status = $orderItem->getData("hotai_point_deduction_point_progress_status");
                $this->writeLog($itemLogTitle . "order item doesn't need re-deduction due to status({$status}).");
                continue;
            }

            try {
                $oldTraceNo             = $orderItem->getData("hotai_point_deduction_point_trace_no");
                $oldTransSN             = $orderItem->getData("hotai_point_deduction_point_trans_s_n");
                $oldTransDatetime       = $orderItem->getData("hotai_point_deduction_point_trans_datetime");
                $this->walkthroughLog[] = "Ready to reset deduction point fields.";
                $this->walkthroughLog[] = "Old traceNo: {$oldTraceNo}, old transSN: {$oldTransSN}, old transDatetime: {$oldTransDatetime}.";
                $this->resetDeductionFieldInOrderItem($orderItem);
                $this->walkthroughLog[] = "Reset deduction point fields done.";

                $transSN                = $this->apiHelper->generateTransSNForApiDeductionPoint($order, $orderItem);
                $transTimestamp         = time();
                $transDesc              = $this->apiHelper->generateTransDescForApiDeductionPoint($order, $orderItem);
                $this->walkthroughLog[] = "Ready to request DeductionPoint API.";
                $apiFlowResult = $this->apiFlowHelper->deductionFlow(
                    $order->getCustomerId(),
                    $transSN,
                    $transTimestamp,
                    ($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount()),
                    $requestPoint,
                    $transDesc
                );

                if (!isset($apiFlowResult['isSuccess']) || $apiFlowResult['isSuccess'] !== true) {
                    throw new \Exception("Deduction point API flow result is not success.");
                }

                if (empty($apiFlowResult['traceNo'])) {
                    throw new \Exception("TraceNo is empty.");
                }

                $newTraceNo = $apiFlowResult['traceNo'];
                $this->walkthroughLog = array_merge($this->walkthroughLog, $apiFlowResult['walkthroughLog'] ?? []);

                $this->orderItemUpdateIfSuccess($orderItem, $newTraceNo, $transSN, $transTimestamp);
            } catch (\Exception $e) {
                $this->walkthroughLog = array_merge($this->walkthroughLog, $apiFlowResult['walkthroughLog'] ?? []);
                $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
                $this->writeLog($logTitle . "exception data: " . json_encode([
                    "Title"            => $logTitle,
                    "OrderItemId"      => $orderItem->getId(),
                    "ExceptionMessage" => $e->getMessage(),
                    "WalkthroughLog"   => $this->walkthroughLog,
                ], JSON_UNESCAPED_UNICODE));
                $this->orderItemUpdateIfFail($orderItem, $e->getMessage());

                throw $e;
            }
        }

        $this->updateDeductionCompleteStatusToOrder($order);

        $this->writeLog($logTitle . "re-deduction point for payment retry observer end.");
    }

    /**
     * 檢查order的整體兌點狀態是否處於"進行中"
     * 處於"進行中"狀態代表其他邏輯可能正在進行相關處理, 為避免衝突此時不能對此order進行後續流程
     *
     * @param Order $order
     * @return boolean
     */
    protected function checkIfOrderProcessingDeductionFlow(Order $order): bool
    {
        $status = $order->getData("hotai_point_deduction_point_complete");

        if ($status == CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_COMMITTING) {
            return true;
        }

        if ($status == CommonHelper::DEDUCTION_POINT_COMPLETE_STATUS_CANCELING) {
            return true;
        }

        return false;
    }

    /**
     * 檢查order item的兌點狀態是否需要再做一次兌點圈存
     * 如果狀態為"已取消"則需要
     *
     * @param OrderItem $orderItem
     * @return boolean
     */
    protected function checkIfOrderItemNeedReDeductionByStatus(OrderItem $orderItem): bool
    {
        $status = $orderItem->getData("hotai_point_deduction_point_progress_status");

        return ($status == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL);
    }

    /**
     * 將order item紀錄(前一次)兌點相關欄位清空
     *
     * @param OrderItem $orderItem
     * @return void
     */
    protected function resetDeductionFieldInOrderItem(OrderItem $orderItem): void
    {
        $orderItem->setData("hotai_point_deduction_point_trace_no", null);
        $orderItem->setData('hotai_point_deduction_point_trans_s_n', null);
        $orderItem->setData('hotai_point_deduction_point_trans_datetime', null);

        $this->orderItemRepository->save($orderItem);
    }

    /**
     * 若和泰點數API請求成功, 對order item相關欄位進行更新
     *
     * @param OrderItem $orderItem
     * @param string $traceNo
     * @param string $transSN
     * @param int|string $transTimestamp
     * @param string $message
     * @return void
     */
    protected function orderItemUpdateIfSuccess(
        OrderItem $orderItem,
        string $traceNo,
        string $transSN,
        int|string $transTimestamp,
        string $message = ""
    ): void {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $orderItem->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Re-deduction point request success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp($transTimestamp);

        $orderItem->setData('hotai_point_deduction_point_trace_no', $traceNo);
        $orderItem->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_MADE);
        $orderItem->setData('hotai_point_deduction_point_trans_s_n', $transSN);
        $orderItem->setData('hotai_point_deduction_point_trans_datetime', $taiwanDateObj->format("Y-m-d H:i:s"));
        $orderItem->setData('hotai_point_deduction_point_memo', $memoMessage);

        $this->orderItemRepository->save($orderItem);
    }

    /**
     * 若和泰點數API請求失敗, 對order item相關欄位進行更新
     *
     * @param OrderItem $orderItem
     * @param string $message
     * @return void
     */
    protected function orderItemUpdateIfFail(OrderItem $orderItem, string $message): void
    {
        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $orderItem->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Re-deduction point fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        // 這裡不要把狀態改成異常, 因為用戶下一次按重新付款圈存可能成功
        // $orderItem->setData('hotai_point_deduction_point_progress_status', CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL);
        $orderItem->setData('hotai_point_deduction_point_memo', $memoMessage);

        $this->orderItemRepository->save($orderItem);
    }

    /**
     * 完成圈存流程後將order的兌點整體狀態欄位更新為"預設"=>等待後續支付成功或失敗處理
     *
     * @param Order $order
     * @return void
     */
    protected function updateDeductionCompleteStatusToOrder(Order $order): void
    {
        $order->setData("hotai_point_deduction_point_complete", CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT);

        $this->orderRepository->save($order);
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
