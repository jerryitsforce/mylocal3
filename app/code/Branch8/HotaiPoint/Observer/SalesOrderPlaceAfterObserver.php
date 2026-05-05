<?php

namespace Branch8\HotaiPoint\Observer;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use Magento\Framework\App\ResourceConnection;
use Branch8\HotaiPoint\Model\DeductionPointCancelRetryStatus;
use Branch8\HotaiPoint\Helper\ApiFlow as ApiFlowHelper;
use Branch8\WebkulMpsplitorder\Model\ErrorCodeMapping;

class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Observer';
    private const LOG_SUBFOLDER_NAME = 'SalesOrderPlaceAfterObserver';
    private const DEBUG_LOG_OPTION = LogOption::LOG_SALES_ORDER_PLACE_AFTER_OBSERVER;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var QuoteItemRepository */
    protected $quoteItemRepository;

    /** @var QuoteItemFactory */
    protected $quoteItemFactory;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var ApiFlowHelper */
    protected $apiFlowHelper;

    /** @var ErrorCodeMapping */
    protected $errorCodeMapping;

    protected $logFileName;
    protected $walkthroughLog;

    protected $or;

    public function __construct(
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        QuoteRepository $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        QuoteItemFactory $quoteItemFactory,
        ResourceConnection $resourceConnection,
        ManagerInterface $eventManager,
        ApiFlowHelper $apiFlowHelper,
        ErrorCodeMapping $errorCodeMapping
    ) {
        $this->apiHelper           = $apiHelper;
        $this->commonHelper        = $commonHelper;
        $this->quoteRepository     = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->quoteItemFactory    = $quoteItemFactory;
        $this->resourceConnection  = $resourceConnection;
        $this->eventManager        = $eventManager;
        $this->apiFlowHelper       = $apiFlowHelper;
        $this->errorCodeMapping    = $errorCodeMapping;

        $this->logFileName = "sales_order_place_after_observer_" . date("Y_m_d") . ".log";
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data    = $observer->getEvent()->getData();
        $order   = $observer->getOrder();
        $quoteId = $order->getQuoteId();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote            = $this->quoteRepository->get(cartId: $quoteId);
        $orderIncrementId = $order->getIncrementId();
        $orderItems       = $order->getAllVisibleItems();
        $logTitle         = "Quote id: {$quoteId}, Order increment id: {$orderIncrementId}, ";

        $this->writeLog($logTitle . "HotaiPointSalesOrderPlaceAfterObserver handle start.");
        $this->writeLog($logTitle . "EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_START event fire.");
        try {
            $this->eventManager->dispatch(CommonHelper::EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_START, [
                "quoteId" => $quoteId,
            ]);
        } catch (\Exception $e) {
            $this->writeLog($logTitle . "Exception during hotai_point_sales_order_place_after_observer_execute_start.");
            $this->eventManager->dispatch(CommonHelper::EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_EXCEPTION, [
                "quoteId" => $quoteId,
            ]);

            throw $e;
        }

        try {
            $pointUsedTotal = (float) $quote->getData("point_used_total");
            if (empty($pointUsedTotal) || $pointUsedTotal <= 0) {
                $this->writeLog($logTitle . "pointUsedTotal: {$pointUsedTotal}, HotaiPointSalesOrderPlaceAfterObserver handle end.");
                return;
            }

            $this->writeLog($logTitle . "pointUsedTotal: {$pointUsedTotal}, ready to get available point from Hotai point API.");
            $getTransInfoResponse = $this->apiHelper->requestApiGetTransInfo($order->getCustomerId());
            $availablePoint       = $this->apiHelper->getPointFromResponse($getTransInfoResponse);
            $this->writeLog($logTitle . "available point: {$availablePoint}");

            if ($pointUsedTotal > $availablePoint) {
                $message = $this->errorCodeMapping->getRawErrorMessageByErrorCode(ErrorCodeMapping::ERROR_CODE_SPE03);
                throw new \Exception($message);
            }
        } catch (\Exception $e) {
            $this->writeLog($logTitle . "Exception message: {$e->getMessage()}, last API request data: {$this->apiHelper->getRequestDataString()}");
            $this->eventManager->dispatch(CommonHelper::EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_EXCEPTION, [
                "quoteId" => $quoteId,
            ]);

            throw $e;
        }

        foreach ($orderItems as $orderItem) {
            $apiFlowResult = [];
            try {
                $this->apiHelper->reset();
                $this->walkthroughLog   = [];
                $this->walkthroughLog[] = "Sales order place after response observer handle start, observer received data: " . json_encode($data);
                $this->writeLog($logTitle . "ready to handle order item(quote item id: {$orderItem->getQuoteItemId()}).");

                $requestPoint = $orderItem->getData("row_total_point_used");
                if (empty($requestPoint)) {
                    $this->writeLog($logTitle . "empty request point: {$requestPoint}, continue.");
                    continue;
                }

                $transSN                = $this->apiHelper->generateTransSNForApiDeductionPoint($order, $orderItem);
                $transTimestamp         = time();
                $transDesc              = $this->apiHelper->generateTransDescForApiDeductionPoint($order, $orderItem);

                $this->writeLog($logTitle . "ready to enter deductionFlow: " . json_encode([
                    "TransSN" => $transSN,
                    "TransTimestamp" => $transTimestamp,
                    "TransDesc" => $transDesc,
                    "RequestPoint" => $requestPoint,
                ], JSON_UNESCAPED_UNICODE));

                $apiFlowResult = $this->apiFlowHelper->deductionFlow(
                    $order->getCustomerId(),
                    $transSN,
                    $transTimestamp,
                    ($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount()),
                    $requestPoint,
                    $transDesc
                );

                $this->writeLog($logTitle . "deductionFlow done, result: " . json_encode([
                    "ApiFlowResult" => $apiFlowResult,
                ], JSON_UNESCAPED_UNICODE));

                if (!isset($apiFlowResult['isSuccess']) || $apiFlowResult['isSuccess'] !== true) {
                    throw new \Exception("Deduction point API flow result is not success.");
                }

                if (empty($apiFlowResult['traceNo'])) {
                    throw new \Exception("TraceNo is empty.");
                }

                $this->walkthroughLog = array_merge($this->walkthroughLog, $apiFlowResult['walkthroughLog'] ?? []);
                $traceNo = $apiFlowResult['traceNo'];

                $this->quoteItemUpdateIfSuccess($order->getQuoteId(), $orderItem->getQuoteItemId(), $traceNo, $transSN, $transTimestamp);

                $this->writeLog($logTitle . "handle done.");
            } catch (\Exception $e) {
                $this->walkthroughLog = array_merge($this->walkthroughLog ?? [], $apiFlowResult['walkthroughLog'] ?? []);

                $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
                $this->writeLog(json_encode([
                    "Title"            => $logTitle,
                    "Message"          => "catch exception during SalesOrderPlaceAfterObserver deduction flow.",
                    "QuoteItemId"      => $orderItem->getQuoteItemId(),
                    "ExceptionMessage" => $e->getMessage(),
                    "WalkthroughLog"   => $this->walkthroughLog,
                ], JSON_UNESCAPED_UNICODE));

                try {
                    $this->eventManager->dispatch(CommonHelper::EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_EXCEPTION, [
                        "quoteId" => $quoteId,
                    ]);
                } catch (\Exception $innerException) {
                    $exceptionTitle = "Exception during event dispatch(" . CommonHelper::EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_EXCEPTION . ")";
                    $this->walkthroughLog[] = $exceptionTitle . ": " . $innerException->getMessage();
                    $this->writeLog(json_encode([
                        "Title"            => $exceptionTitle,
                        "Message"          => "catch exception during exception event dispatch.",
                        "QuoteItemId"      => $orderItem->getQuoteItemId(),
                        "ExceptionMessage" => $innerException->getMessage(),
                    ], JSON_UNESCAPED_UNICODE));
                }

                $this->writeLog(json_encode([
                    "Title"            => $logTitle,
                    "QuoteItemId"      => $orderItem->getQuoteItemId(),
                    "Message"          => "after exception event dispatch, ready to update quote item if fail.",
                ], JSON_UNESCAPED_UNICODE));

                $this->quoteItemUpdateIfFail($order->getQuoteId(), $orderItem->getQuoteItemId(), $e->getMessage());

                $this->writeLog(json_encode([
                    "Title"            => $logTitle,
                    "QuoteItemId"      => $orderItem->getQuoteItemId(),
                    "Message"          => "after quote item update if fail, ready to throw exception.",
                    "WalkthroughLog"   => $this->walkthroughLog,
                ], JSON_UNESCAPED_UNICODE));

                $message = $this->errorCodeMapping->getRawErrorMessageByErrorCode(ErrorCodeMapping::ERROR_CODE_SPE05);
                throw new \Exception($message);
            }
        }

        $this->eventManager->dispatch(CommonHelper::EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_END, [
            "quoteId" => $quoteId,
        ]);

        $this->writeLog($logTitle . "HotaiPointSalesOrderPlaceAfterObserver handle end.");
    }

    /**
     * 若和泰點數API請求成功, 對quote item相關欄位進行更新
     *
     * @param integer $quoteId
     * @param integer $quoteItemId
     * @param string $traceNo
     * @param string $transSN
     * @param int|string $transTimestamp
     * @param string $message
     * @return void
     */
    private function quoteItemUpdateIfSuccess(
        int $quoteId,
        int $quoteItemId,
        string $traceNo,
        string $transSN,
        int|string $transTimestamp,
        string $message = ""
    ): void {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote     = $this->quoteRepository->get($quoteId);
        $quoteItem = $quote->getItemById($quoteItemId);
        $this->writeLog("success quote item load target: {$quoteItemId}");
        // $quoteItem = $this->quoteItemFactory->create()->load($quoteItemId, 'item_id');

        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $quoteItem->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point request success.(It's quote item at the moment)",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp($transTimestamp);

        $connection = $this->resourceConnection->getConnection();

        try {
            $connection->beginTransaction();

            $table = $connection->getTableName('quote_item');

            $data = [
                "hotai_point_deduction_point_trace_no"        => $traceNo,
                "hotai_point_deduction_point_progress_status" => CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_MADE,
                "hotai_point_deduction_point_trans_s_n"       => $transSN,
                "hotai_point_deduction_point_trans_datetime"  => $taiwanDateObj->format("Y-m-d H:i:s"),
                "hotai_point_deduction_point_memo"            => $memoMessage
            ];

            $connection->update(
                $table,
                $data,
                ['item_id = ?' => $quoteItemId]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->writeLog("quoteItemUpdateIfSuccess handle done.{$traceNo}|{$transSN}|{$memoMessage}");
    }

    /**
     * 若和泰點數API請求失敗, 對quote item相關欄位進行更新
     *
     * @param integer $quoteId
     * @param integer $quoteItemId
     * @param string $message
     * @return void
     */
    private function quoteItemUpdateIfFail(
        int $quoteId,
        int $quoteItemId,
        string $message
    ): void {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote     = $this->quoteRepository->get($quoteId);
        $quoteItem = $quote->getItemById($quoteItemId);
        $this->writeLog("quoteItemUpdateIfFail item target: {$quoteItemId}");

        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $quoteItem->getData('hotai_point_deduction_point_memo'),
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "Deduction point fail.(It's quote item at the moment)",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $connection = $this->resourceConnection->getConnection();
        try {
            $connection->beginTransaction();

            $table = $connection->getTableName('quote_item');

            $data = [
                // 交給app/code/Branch8/HotaiPoint/Cron/DeductionCancelRetry.php, 不要改狀態
                // "hotai_point_deduction_point_progress_status" => CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL,
                "hotai_point_deduction_point_memo" => $memoMessage
            ];

            $connection->update(
                $table,
                $data,
                ['item_id = ?' => $quoteItemId]
            );

            // -------
            $quoteTable = $connection->getTableName('quote');
            $quoteData  = [
                "hotai_point_deduction_point_cancel_retry_status" => DeductionPointCancelRetryStatus::STATUS_NEED_RETRY,
            ];
            $connection->update(
                $quoteTable,
                $quoteData,
                ['entity_id = ?' => $quoteId]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            $this->writeLog("quoteItemUpdateIfFail update exception, item target: {$quoteItemId}, message: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * 紀錄log
     *
     * @param string $message
     * @return void
     */
    private function writeLog(string $message): void
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }
}
