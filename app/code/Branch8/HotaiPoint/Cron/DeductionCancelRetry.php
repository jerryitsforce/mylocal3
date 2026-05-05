<?php

namespace Branch8\HotaiPoint\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Branch8\HotaiPoint\Model\DeductionPointCancelRetryStatus;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord as HotaiPointApiRecordModel;
use Branch8\HotaiPoint\Model\HotaiPointApiRecordRepository;

class DeductionCancelRetry
{
    const LOG_FOLDER_NAME   = 'HotaiPoint/Cron/DeductionCancelRetry';
    const RETRY_COUNT_LIMIT = 6;
    private const DEBUG_LOG_OPTION = LogOption::LOG_DEDUCTION_CANCEL_RETRY;

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

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var HotaiPointApiRecordRepository */
    protected $hotaiPointApiRecordRepository;

    protected $walkthroughLog;
    protected $flowFail            = false;
    protected $failCounterHitLimit = false;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        ResourceConnection $resourceConnection,
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        HotaiPointApiRecordRepository $hotaiPointApiRecordRepository
    ) {
        $this->hotaiCoreCommonHelper         = $hotaiCoreCommonHelper;
        $this->orderCollectionFactory        = $orderCollectionFactory;
        $this->orderRepository               = $orderRepository;
        $this->resourceConnection            = $resourceConnection;
        $this->apiHelper                     = $apiHelper;
        $this->commonHelper                  = $commonHelper;
        $this->hotaiPointApiRecordRepository = $hotaiPointApiRecordRepository;

        $this->walkthroughLog = [];
    }

    public function execute()
    {
        $this->commonHelper->writeLogIfEnabled(
            "DeductionCancelRetry cron start.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $quoteArray = $this->getTargetQuoteRecordArray();

        foreach ($quoteArray as $quote) {
            try {
                $quoteId = $quote['entity_id'];
                $this->commonHelper->writeLogIfEnabled(
                    "Quote ID: {$quoteId}, ready to handle.",
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );

                $this->flowFail            = false;
                $this->failCounterHitLimit = false;

                if ($this->checkIfQuoteHasOrder($quoteId)) {
                    $this->updateQuoteIfHasOrder($quoteId);

                    $this->commonHelper->writeLogIfEnabled(
                        "Quote ID: {$quoteId} has order, skip.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );

                    continue;
                }

                $this->handleCancel($quote);

                if ($this->failCounterHitLimit) {
                    $this->updateQuoteFieldIffailCounterHitLimit($quote);
                }

                $this->updateQuoteFieldAfterAllDeductionCancelProcess($quote);
            } catch (\Throwable $th) {
                $this->commonHelper->writeLogIfEnabled(
                    "Quote ID: {$quoteId}, exception while executing cron flow, message: " . $th->getMessage(),
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );
            }
        }

        $this->commonHelper->writeLogIfEnabled(
            "DeductionCancelRetry cron end.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    protected function getTargetQuoteRecordArray(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $quoteTableName = $connection->getTableName('quote');

        $select = $connection->select()->from(
            $quoteTableName,
            [
                'entity_id',
                'customer_id',
                'hotai_point_deduction_point_cancel_retry_status',
            ]
        );

        $select->where(
            DeductionPointCancelRetryStatus::FIELD_NAME . ' = ?',
            DeductionPointCancelRetryStatus::STATUS_NEED_RETRY
        );

        $result = $connection->fetchAll($select);

        return $result;
    }

    protected function checkIfQuoteHasOrder(int|string $quoteId): bool
    {
        $connection     = $this->resourceConnection->getConnection();
        $orderTableName = $connection->getTableName('sales_order');

        $select = $connection->select()
            ->from(
                $orderTableName,
                ['entity_id']
            )->where(
                'quote_id = ?',
                $quoteId
            );

        $result = $connection->fetchOne($select);

        return !empty($result);
    }

    protected function handleCancel(array $quote)
    {
        $quoteId  = $quote['entity_id'];
        $logTitle = "Quote ID: " . $quoteId . ", ";
        $this->commonHelper->writeLogIfEnabled(
            $logTitle . "handleCancel function start.",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $quoteItemArray = $this->getQuoteItemRecordArray($quote['entity_id']);

        $quoteItemIdsString = $this->getItemIdsString($quoteItemArray);
        $this->commonHelper->writeLogIfEnabled(
            $logTitle . "ready to handle quote items(ids: {$quoteItemIdsString}).",
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        foreach ($quoteItemArray as $quoteItem) {
            try {
                $this->apiHelper->reset();
                $quoteItemId            = $quoteItem['item_id'];
                $this->walkthroughLog   = [];
                $this->walkthroughLog[] = "DeductionCancelRetry cron handle start, quote ID: " . $quoteId;
                $this->commonHelper->writeLogIfEnabled(
                    $logTitle . "ready to handle quote item(id: {$quoteItemId}).",
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );

                // 檢查 row_total_point_used 有值才繼續
                if (!$this->checkIfPointUsedOnItem($quoteItem)) {
                    $rowTotalPointUsed = $quoteItem["row_total_point_used"];
                    $rowTotalPointUsed = is_null($rowTotalPointUsed) ? "null" : $rowTotalPointUsed;

                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "quote item(id: {$quoteItemId}) skip due to row_total_point_used value: {$rowTotalPointUsed}.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );

                    continue;
                }

                // 如果沒有點數圈存相關資料, 試圖從apiRecord裡面取得
                if (!$this->checkDeductionPointData($quoteItem)) {
                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "try to restore deduction data from api record.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );

                    $this->restoreDeductionPointDataFromApiRecord($quote, $quoteItem);
                }

                if (!$this->checkDeductionProgress($quoteItem)) {
                    $progressStatus = $quoteItem["hotai_point_deduction_point_progress_status"];
                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "quote item(id: {$quoteItemId}) skip due to progress_status value: {$progressStatus}.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                    continue;
                }

                $traceNo = $quoteItem["hotai_point_deduction_point_trace_no"];

                if (!$traceNo) {
                    $this->commonHelper->writeLogIfEnabled(
                        $logTitle . "quote item(id: {$quoteItemId}) skip due to hotai_point_deduction_point_trace_no value is NULL.",
                        self::LOG_FOLDER_NAME,
                        self::DEBUG_LOG_OPTION
                    );
                    continue;
                }

                // 和泰點數cancel流程
                $this->walkthroughLog[] = "Ready to request Cancel API.";
                $cancelResponse         = $this->apiHelper->requestApiCancel(
                    (int) $quote['customer_id'],
                    $traceNo,
                    [
                        $this->apiHelper::API_RESPONSE_CODE_SUCCESS,
                        $this->apiHelper::API_RESPONSE_CODE_TRANSACTION_LOCK
                    ]
                );
                $this->walkthroughLog[] = "Cancel API done, request data: " . $this->apiHelper->getRequestDataString();
                $this->walkthroughLog[] = "Cancel API done, response data: " . json_encode($cancelResponse);

                // 判斷cancelResponse是否為transaction lock
                $returnCode = $this->apiHelper->getReturnCodeFromResponse($cancelResponse);
                if ($this->checkIsTransactionLock($returnCode)) {
                    $this->walkthroughLog[] = "Cancel API result is transaction lock, extra handle start.";

                    // 如果是lock則請求解除API
                    $this->walkthroughLog[] = "Ready to request Unlock API.";
                    $unlockResponse         = $this->apiHelper->requestApiUnlockOneId((int) $quote['customer_id']);
                    $this->walkthroughLog[] = "Unlock API done, request data: " . $this->apiHelper->getRequestDataString();
                    $this->walkthroughLog[] = "Unlock API done, response data: " . json_encode($unlockResponse);

                    // 然後再次cancel, 這次預期一定要成功, 所以不傳遞額外allow return code array
                    $this->walkthroughLog[] = "Ready to request Cancel API again after Unlock API request is done.";
                    $cancelAgainResponse    = $this->apiHelper->requestApiCancel(
                        (int) $quote['customer_id'],
                        $traceNo
                    );
                    $this->walkthroughLog[] = "Cancel API again done, request data: " . $this->apiHelper->getRequestDataString();
                    $this->walkthroughLog[] = "Cancel API again done, response data: " . json_encode($cancelAgainResponse);
                }

                $this->commonHelper->writeLogIfEnabled(
                    $logTitle . "quote item(id: {$quoteItemId}) handled by cancel flow done.",
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );

                $this->updateIfSuccess($quoteItem);
            } catch (\Exception $e) {
                $this->flowFail = true;

                $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
                $this->commonHelper->writeLogIfEnabled(
                    $logTitle . "Exception, quote item ID: " . $quoteItemId . ", message: " . $e->getMessage(),
                    self::LOG_FOLDER_NAME,
                    self::DEBUG_LOG_OPTION
                );
                $this->updateIfFail($quoteItem, $e->getMessage());
            }
        }
    }

    protected function getQuoteItemRecordArray(int|string $quoteId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $quoteItemTableName = $connection->getTableName('quote_item');

        $select = $connection->select()->from(
            $quoteItemTableName,
            [
                'item_id',
                'quote_id',
                'row_total_point_used',
                'hotai_point_deduction_point_trace_no',
                'hotai_point_deduction_point_progress_status',
                'hotai_point_deduction_point_trans_s_n',
                'hotai_point_deduction_point_trans_datetime',
                'hotai_point_deduction_point_commit_or_cancel_fail_counter',
                'hotai_point_deduction_point_memo',
            ]
        );

        $select->where('quote_id = ?', $quoteId);

        $result = $connection->fetchAll($select);

        return $result;
    }

    protected function getItemIdsString(array $quoteItemArray): string
    {
        $itemIds = [];

        foreach ($quoteItemArray as $quoteItem) {
            $itemIds[] = $quoteItem['item_id'];
        }

        return implode(",", $itemIds);
    }

    protected function checkIfPointUsedOnItem(array $quoteItem): bool
    {
        return (bool) (float) $quoteItem["row_total_point_used"];
    }

    protected function checkDeductionPointData(array $quoteItem): bool
    {
        if (empty($quoteItem["hotai_point_deduction_point_trace_no"])) {
            return false;
        }

        if (empty($quoteItem["hotai_point_deduction_point_trans_s_n"])) {
            return false;
        }

        if (empty($quoteItem["hotai_point_deduction_point_trans_datetime"])) {
            return false;
        }

        return true;
    }

    protected function restoreDeductionPointDataFromApiRecord(array $quote, array $quoteItem): void
    {
        $connection  = $this->resourceConnection->getConnection();
        $table       = $connection->getTableName('quote_item');
        $quoteId     = $quote['entity_id'];
        $quoteItemId = $quoteItem['item_id'];

        $collection = $this->hotaiPointApiRecordRepository->getCollectionByQuoteInfo($quoteId, $quoteItemId);

        if ($collection->count() != 1) {
            return;
        }

        $apiRecord         = $collection->getFirstItem();
        $syncStatus        = 1;
        $progressStatus    = 1;
        $dataBeforeRestore = [
            "hotai_point_deduction_point_sync_from_quote_item_status" => $quoteItemId["hotai_point_deduction_point_sync_from_quote_item_status"],
            "hotai_point_deduction_point_trace_no"                    => $quoteItemId["hotai_point_deduction_point_trace_no"],
            "hotai_point_deduction_point_progress_status"             => $quoteItemId["hotai_point_deduction_point_progress_status"],
            "hotai_point_deduction_point_trans_s_n"                   => $quoteItemId["hotai_point_deduction_point_trans_s_n"],
            "hotai_point_deduction_point_trans_datetime"              => $quoteItemId["hotai_point_deduction_point_trans_datetime"],
        ];

        $currentMemo = $quoteItem["hotai_point_deduction_point_memo"];
        $memo        = $this->commonHelper->prepareMemoStringForUpdate(
            $currentMemo,
            [
                "Timestamp"           => time(),
                "Datetime(+0)"        => date("Y-m-d H:i:s"),
                "Title"               => "Restore deduction point data from api record.",
                "Data before restore" => $dataBeforeRestore,
                "Data after restore"  => [
                    "hotai_point_deduction_point_sync_from_quote_item_status" => $syncStatus,
                    "hotai_point_deduction_point_trace_no"                    => $apiRecord->getData(HotaiPointApiRecordModel::TRACE_NO),
                    "hotai_point_deduction_point_progress_status"             => $progressStatus,
                    "hotai_point_deduction_point_trans_s_n"                   => $apiRecord->getData(HotaiPointApiRecordModel::TRANS_S_N),
                    "hotai_point_deduction_point_trans_datetime"              => $apiRecord->getData(HotaiPointApiRecordModel::TRANS_DATETIME)
                ],
            ]
        );

        $data = [
            "hotai_point_deduction_point_trace_no"        => $apiRecord->getData(HotaiPointApiRecordModel::TRACE_NO),
            "hotai_point_deduction_point_progress_status" => $progressStatus,
            "hotai_point_deduction_point_trans_s_n"       => $apiRecord->getData(HotaiPointApiRecordModel::TRANS_S_N),
            "hotai_point_deduction_point_trans_datetime"  => $apiRecord->getData(HotaiPointApiRecordModel::TRANS_DATETIME),
            "hotai_point_deduction_point_memo"            => $memo
        ];

        $connection->update(
            $table,
            $data,
            ['item_id = ?' => $quoteItemId]
        );
    }

    protected function checkDeductionProgress(array $quoteItem): bool
    {
        $currentStatus = $quoteItem["hotai_point_deduction_point_progress_status"];

        if ($currentStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_MADE) {
            return true;
        }

        return false;
    }

    protected function updateIfSuccess(array $quoteItem, string $message = ""): void
    {
        $connection  = $this->resourceConnection->getConnection();
        $table       = $connection->getTableName('quote_item');
        $quoteItemId = $quoteItem['item_id'];

        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $quoteItem['hotai_point_deduction_point_memo'],
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "DeductionCancelRetry cron - Deduction point cancel success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $data = [
            "hotai_point_deduction_point_progress_status" => CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL,
            "hotai_point_deduction_point_memo"            => $memoMessage
        ];

        $connection->update(
            $table,
            $data,
            ['item_id = ?' => $quoteItemId]
        );
    }

    protected function updateIfFail(array $quoteItem, string $message): void
    {
        $connection  = $this->resourceConnection->getConnection();
        $table       = $connection->getTableName('quote_item');
        $quoteItemId = $quoteItem['item_id'];

        $memoMessage = $this->commonHelper->prepareMemoStringForUpdate(
            $quoteItem['hotai_point_deduction_point_memo'],
            [
                "Timestamp"      => time(),
                "Datetime"       => date("Y-m-d H:i:s"),
                "Title"          => "DeductionCancelRetry cron - Deduction point cancel fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        $currentFailCounter = $quoteItem["hotai_point_deduction_point_commit_or_cancel_fail_counter"];
        $newFailCounter     = $currentFailCounter + 1;

        if ($newFailCounter >= self::RETRY_COUNT_LIMIT) {
            $this->failCounterHitLimit = true;
        }

        $data = [
            "hotai_point_deduction_point_commit_or_cancel_fail_counter" => $newFailCounter,
            "hotai_point_deduction_point_memo"                          => $memoMessage
        ];

        $connection->update(
            $table,
            $data,
            ['item_id = ?' => $quoteItemId]
        );
    }

    protected function updateQuoteIfHasOrder(int|string $quoteId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName  = $connection->getTableName('quote');

        $data = [
            DeductionPointCancelRetryStatus::FIELD_NAME => DeductionPointCancelRetryStatus::STATUS_RETRY_SUCCESS
        ];

        $connection->update(
            $tableName,
            $data,
            ['entity_id = ?' => $quoteId]
        );
    }

    protected function updateQuoteFieldIffailCounterHitLimit(array $quote): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName  = $connection->getTableName('quote');
        $quoteId    = $quote['entity_id'];

        $data = [
            DeductionPointCancelRetryStatus::FIELD_NAME => DeductionPointCancelRetryStatus::STATUS_RETRY_LIMIT_ERROR
        ];

        $connection->update(
            $tableName,
            $data,
            ['entity_id = ?' => $quoteId]
        );
    }

    protected function updateQuoteFieldAfterAllDeductionCancelProcess(array $quote): bool
    {
        $allSuccessFlag = true;

        $quoteItemArray = $this->getQuoteItemRecordArray($quote['entity_id']);

        foreach ($quoteItemArray as $quoteItem) {
            $progressStatus = $quoteItem['hotai_point_deduction_point_progress_status'];

            if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT) {
                continue;
            }

            if ($progressStatus == CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL) {
                continue;
            }

            $allSuccessFlag = false;
        }

        if ($allSuccessFlag) {
            $connection = $this->resourceConnection->getConnection();
            $tableName  = $connection->getTableName('quote');
            $quoteId    = $quote['entity_id'];

            $data = [
                DeductionPointCancelRetryStatus::FIELD_NAME => DeductionPointCancelRetryStatus::STATUS_RETRY_SUCCESS
            ];

            $connection->update(
                $tableName,
                $data,
                ['entity_id = ?' => $quoteId]
            );
        }

        return $allSuccessFlag;
    }

    protected function checkIsTransactionLock(string $returnCode): bool
    {
        return $returnCode == $this->apiHelper::API_RESPONSE_CODE_TRANSACTION_LOCK;
    }
}
