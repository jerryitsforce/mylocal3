<?php

namespace Branch8\HotaiPoint\Helper;

use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord as HotaiPointApiRecordModel;
use Branch8\HotaiPoint\Model\HotaiPointApiRecordRepository;
use Branch8\HotaiPoint\Model\DeductionPointRetryStatus;
use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class Common
{
    const MAIN_MODULE_LOG_FOLDER = '/HotaiPoint/';
    private const DEBUG_MODULE_NAME = 'Branch8_HotaiPoint';
    private const DEBUG_LOG_OPTION = LogOption::LOG_COMMON;

    const HOTAI_POINT_CONFIG_PATH_API_DOMAIN                                                          = "hotai_point/general/api_domain";
    const HOTAI_POINT_CONFIG_PATH_APP_ID                                                              = "hotai_point/general/app_id";
    const HOTAI_POINT_CONFIG_PATH_APP_KEY                                                             = "hotai_point/general/app_key";
    const HOTAI_POINT_CONFIG_PATH_APP_VERSION                                                         = "hotai_point/general/app_version";
    const HOTAI_POINT_CONFIG_PATH_AES_KEY                                                             = "hotai_point/general/aes_key";
    const HOTAI_POINT_CONFIG_PATH_AES_IV                                                              = "hotai_point/general/aes_iv";
    const HOTAI_POINT_CONFIG_PATH_TRANS_DESC_WHITE_LIST                                               = "hotai_point/general/trans_desc_white_list";
    const HOTAI_POINT_CONFIG_PATH_FTP_ENABLE                                                          = "hotai_point/ftp/enable";
    const HOTAI_POINT_CONFIG_PATH_FTP_HOST                                                            = "hotai_point/ftp/host";
    const HOTAI_POINT_CONFIG_PATH_FTP_USERNAME                                                        = "hotai_point/ftp/username";
    const HOTAI_POINT_CONFIG_PATH_FTP_PASSWORD                                                        = "hotai_point/ftp/password";
    const HOTAI_POINT_CONFIG_PATH_FTP_SYNC_FOLDER_PATH                                                = "hotai_point/ftp/sync_folder_path";
    const HOTAI_POINT_CONFIG_PATH_FTP_REMOVE_SYNC_BACKUP_FILE_AFTER_X_DAYS                            = "hotai_point/ftp/remove_sync_backup_file_after_x_days";
    const HOTAI_POINT_CONFIG_PATH_CRON_CREATED_AFTER_MINUTES_FOR_ADDING_POINT_TO_COMPLETE_SALES_ORDER = "hotai_point/cron/created_after_minutes_for_adding_point_to_complete_sales_order";
    const HOTAI_POINT_CONFIG_PATH_RETURN_POINT_FIELDS_CLEAN_TRACKING                                  = "hotai_point/tracking_log/return_point_fields_clean_tracking";

    const DEDUCTION_POINT_COMPLETE_STATUS_EXCEPTION_FAIL = -1;
    const DEDUCTION_POINT_COMPLETE_STATUS_DEFAULT        = 0;
    const DEDUCTION_POINT_COMPLETE_STATUS_DONE           = 1;
    const DEDUCTION_POINT_COMPLETE_STATUS_COMMITTING     = 2;
    const DEDUCTION_POINT_COMPLETE_STATUS_CANCELING      = 3;

    const DEDUCTION_POINT_PROGRESS_STATUS_EXCEPTION_FAIL     = -1;
    const DEDUCTION_POINT_PROGRESS_STATUS_DEFAULT            = 0;
    const DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_MADE   = 1;
    const DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT = 2;
    const DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL = 3;

    const EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_START     = "hotai_point_sales_order_place_after_observer_execute_start";
    const EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_END       = "hotai_point_sales_order_place_after_observer_execute_end";
    const EVENT_PLACE_ORDER_AFTER_OBSERVER_EXECUTE_EXCEPTION = "hotai_point_sales_order_place_after_observer_execute_exception";
    const EVENT_DEDUCTION_COMMIT_FLOW_END                    = "hotai_point_deduction_commit_flow_end";
    const EVENT_DEDUCTION_CANCEL_FLOW_END                    = "hotai_point_deduction_cancel_flow_end";
    const EVENT_RETURN_FLOW_END                              = "hotai_point_return_flow_end";

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var File */
    protected $file;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var QuoteRepository */
    protected $quoteRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var HotaiPointApiRecordRepository */
    protected $hotaiPointApiRecordRepository;

    /** @var PointMoneyConfigHelper */
    protected $pointMoneyConfigHelper;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        File $file,
        DirectoryList $directoryList,
        QuoteRepository $quoteRepository,
        OrderItemRepository $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        HotaiPointApiRecordRepository $hotaiPointApiRecordRepository,
        PointMoneyConfigHelper $pointMoneyConfigHelper,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->scopeConfig                   = $scopeConfig;
        $this->file                          = $file;
        $this->directoryList                 = $directoryList;
        $this->quoteRepository               = $quoteRepository;
        $this->orderItemRepository           = $orderItemRepository;
        $this->orderRepository               = $orderRepository;
        $this->hotaiPointApiRecordRepository = $hotaiPointApiRecordRepository;
        $this->pointMoneyConfigHelper        = $pointMoneyConfigHelper;
        $this->hotaiCoreCommonHelper         = $hotaiCoreCommonHelper;
    }

    public function getHotaiPointConfig($configPath)
    {
        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * 使用 bc 乘法計算 row_total_point_discount
     * row_total_point_discount = row_total_point_used * 後台設定的點數比值
     * 根據文件, 點數會是整數不含小數點
     *
     * @param int|float|string $rowTotalPointUsed
     * @return int
     */
    protected function calculateRowTotalPointDiscount($rowTotalPointUsed): int
    {
        $pointRatio = $this->pointMoneyConfigHelper->getRatio();
        $rowTotalPointDiscount = bcmul((string) $rowTotalPointUsed, (string) $pointRatio, 0);
        return (int) $rowTotalPointDiscount;
    }

    /**
     * 將新增的備註資料和現有的hotai_point資料庫備註欄位內容(json字串)結合
     *
     * @param string $currentMemoString
     * @param array $insertMemoArray
     * @return string
     */
    public function prepareMemoStringForUpdate(string|null $currentMemoString, array $insertMemoArray): string
    {
        if (empty($currentMemoString)) {
            return json_encode([$insertMemoArray]);
        }

        $memoArray = json_decode($currentMemoString, true);

        if (empty($memoArray)) {
            return json_encode([$insertMemoArray]);
        }

        array_push($memoArray, $insertMemoArray);

        return json_encode($memoArray, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 更新"和泰點數最後執行兌點相關處理時間"欄位
     * (hotai_point_deduction_point_last_handle_time)
     *
     * @param integer $orderId
     * @return void
     */
    public function updateDeductionPointLastHandleTime(int $orderId): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        $order->setData('hotai_point_deduction_point_last_handle_time', date('Y-m-d H:i:s'));

        $this->orderRepository->save($order);
    }

    public function syncHotaiPointFieldsForOrder(Order $order, string $logFolder): void
    {
        $logTitle = "Order ID: " . $order->getId() . ", ";

        $this->writeLogIfEnabled(
            $logTitle . "syncHotaiPointFieldsForOrder handle start.",
            $logFolder,
            self::DEBUG_LOG_OPTION
        );

        foreach ($order->getAllVisibleItems() as $item) {
            $this->syncHotaiPointFieldsFromQuoteItem($item, $order, $logFolder);
        }

        // check if point_used_total is equal to items sum
        $isPointUsedTotalEqualToItemsSum = $this->checkIfPointUsedTotalEqualToItemsSum($order);

        $this->writeLogIfEnabled(
            $logTitle . "syncHotaiPointFieldsFromQuoteItem check if point_used_total is equal to items sum result: " . json_encode([
                "point_used_total" => $order->getData('point_used_total'),
                "is_equal" => $isPointUsedTotalEqualToItemsSum,
            ]),
            $logFolder,
            self::DEBUG_LOG_OPTION
        );

        if (!$isPointUsedTotalEqualToItemsSum) {
            $this->writeLogIfEnabled(
                $logTitle . "ready to restore deduction point data from api record for all items.",
                $logFolder,
                self::DEBUG_LOG_OPTION
            );

            foreach ($order->getAllVisibleItems() as $item) {
                try {
                    $this->restoreDeductionPointDataFromApiRecordIfSyncIssue($item, $order);
                    $this->writeLogIfEnabled(
                        $logTitle . "order item(id: {$item->getId()}) restore deduction point data from api record done.",
                        $logFolder,
                        self::DEBUG_LOG_OPTION
                    );
                } catch (\Exception $e) {
                    $this->writeLogIfEnabled(
                        $logTitle . "order item(id: {$item->getId()}) restore deduction point data from api record exception(item not using point is possible): " . $e->getMessage(),
                        $logFolder,
                        self::DEBUG_LOG_OPTION
                    );
                }
            }

            $this->writeLogIfEnabled(
                $logTitle . "restore deduction point data from api record for all items done.",
                $logFolder,
                self::DEBUG_LOG_OPTION
            );
        }

        $this->writeLogIfEnabled(
            $logTitle . "syncHotaiPointFieldsForOrder handle end.",
            $logFolder,
            self::DEBUG_LOG_OPTION
        );
    }

    /**
     * 將quote_item的資料同步過來
     * (根據hotai_point_deduction_point_sync_from_quote_item_status判斷是否同步過)
     *
     * @param OrderItem $item
     * @param integer $quoteId
     * @return void
     */
    public function syncHotaiPointFieldsFromQuoteItem(OrderItem $item, Order $order, string $logFolder): void
    {
        $logTitle = "Order ID: " . $order->getId() . ", ";
        $logTitle .= "Order Item ID: " . $item->getId() . ", ";

        $this->writeLogIfEnabled(
            $logTitle . "syncHotaiPointFieldsFromQuoteItem handle start.",
            $logFolder,
            self::DEBUG_LOG_OPTION
        );

        if ($item->getData("hotai_point_deduction_point_sync_from_quote_item_status") == 1) {
            $this->writeLogIfEnabled(
                $logTitle . "syncHotaiPointFieldsFromQuoteItem skip due to sync_from_quote_item_status is 1.",
                $logFolder,
                self::DEBUG_LOG_OPTION
            );

            return;
        }

        $this->writeLogIfEnabled(
            $logTitle . "syncHotaiPointFieldsFromQuoteItem handle continue.",
            $logFolder,
            self::DEBUG_LOG_OPTION
        );

        $quoteId = $order->getQuoteId();
        $quoteItemId = $item->getQuoteItemId();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote     = $this->quoteRepository->get($quoteId);
        $quoteItem = $quote->getItemById($quoteItemId);

        $pointData = [
            "row_total_point_used" => null,
            "row_total_point_discount" => null,
            "hotai_point_deduction_point_sync_from_quote_item_status" => null,
            "hotai_point_deduction_point_trace_no" => null,
            "hotai_point_deduction_point_progress_status" => null,
            "hotai_point_deduction_point_trans_s_n" => null,
            "hotai_point_deduction_point_trans_datetime" => null,
            "hotai_point_deduction_point_commit_or_cancel_fail_counter" => null,
            "hotai_point_deduction_point_memo" => null,
        ];

        if (is_null($quoteItem) || $quoteItem === false || empty($quoteItem->getItemId())) {
            $pointData = $this->getDeductionPointDataForSyncIfQuoteItemNotFound($item, $quoteId);
            $pointData["hotai_point_deduction_point_commit_or_cancel_fail_counter"] = $item->getData("hotai_point_deduction_point_commit_or_cancel_fail_counter");
            $pointData["hotai_point_deduction_point_memo"] = $item->getData("hotai_point_deduction_point_memo");
        } else {
            $pointData["row_total_point_used"] = $quoteItem->getData("row_total_point_used");
            $pointData["row_total_point_discount"] = $quoteItem->getData("row_total_point_discount");
            $pointData["hotai_point_deduction_point_sync_from_quote_item_status"] = 1;
            $pointData["hotai_point_deduction_point_trace_no"] = $quoteItem->getData("hotai_point_deduction_point_trace_no");
            $pointData["hotai_point_deduction_point_progress_status"] = $quoteItem->getData("hotai_point_deduction_point_progress_status");
            $pointData["hotai_point_deduction_point_trans_s_n"] = $quoteItem->getData("hotai_point_deduction_point_trans_s_n");
            $pointData["hotai_point_deduction_point_trans_datetime"] = $quoteItem->getData("hotai_point_deduction_point_trans_datetime");
            $pointData["hotai_point_deduction_point_commit_or_cancel_fail_counter"] = $quoteItem->getData("hotai_point_deduction_point_commit_or_cancel_fail_counter");
            $pointData["hotai_point_deduction_point_memo"] = $quoteItem->getData("hotai_point_deduction_point_memo");
        }

        // row_total_point fields will be synced from the start of sales_order_item creation, so no need to sync here.
        // $item->setData(
        //     "row_total_point_used",
        //     $pointData["row_total_point_used"]
        // );

        // $item->setData(
        //     "row_total_point_discount",
        //     $pointData["row_total_point_discount"]
        // );

        $item->setData(
            "hotai_point_deduction_point_sync_from_quote_item_status",
            $pointData["hotai_point_deduction_point_sync_from_quote_item_status"]
        );

        $item->setData(
            "hotai_point_deduction_point_trace_no",
            $pointData["hotai_point_deduction_point_trace_no"]
        );

        $item->setData(
            "hotai_point_deduction_point_progress_status",
            $pointData["hotai_point_deduction_point_progress_status"]
        );

        $item->setData(
            "hotai_point_deduction_point_trans_s_n",
            $pointData["hotai_point_deduction_point_trans_s_n"]
        );

        $item->setData(
            "hotai_point_deduction_point_trans_datetime",
            $pointData["hotai_point_deduction_point_trans_datetime"]
        );

        $item->setData(
            "hotai_point_deduction_point_commit_or_cancel_fail_counter",
            $pointData["hotai_point_deduction_point_commit_or_cancel_fail_counter"]
        );

        $item->setData(
            "hotai_point_deduction_point_memo",
            $pointData["hotai_point_deduction_point_memo"]
        );

        $this->orderItemRepository->save($item);

        $this->writeLogIfEnabled(
            $logTitle . "syncHotaiPointFieldsFromQuoteItem orderItemRepository save done.",
            $logFolder,
            self::DEBUG_LOG_OPTION
        );
    }

    public function checkIfPointUsedTotalEqualToItemsSum(Order $inputOrder): bool
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($inputOrder->getId());

        $orderPointUsedTotal = $order->getData('point_used_total');
        $sumItemPointUsed = 0;

        foreach ($order->getAllVisibleItems() as $item) {
            $sumItemPointUsed += (int) $item->getData('row_total_point_used');
        }

        return ((int)$orderPointUsedTotal === (int)$sumItemPointUsed);
    }

    public function getDeductionPointDataForSyncIfQuoteItemNotFound(OrderItem $item, int $quoteId): array
    {
        $pointData = [
            "row_total_point_used" => null,
            "row_total_point_discount" => null,
            "hotai_point_deduction_point_sync_from_quote_item_status" => null,
            "hotai_point_deduction_point_trace_no" => null,
            "hotai_point_deduction_point_progress_status" => null,
            "hotai_point_deduction_point_trans_s_n" => null,
            "hotai_point_deduction_point_trans_datetime" => null,
            "hotai_point_deduction_point_commit_or_cancel_fail_counter" => null,
            "hotai_point_deduction_point_memo" => null,
        ];

        $quoteItemId = $item->getQuoteItemId();

        $collection = $this->hotaiPointApiRecordRepository->getCollectionByQuoteInfo($quoteId, $quoteItemId);
        $collection->setOrder(HotaiPointApiRecordModel::RECORD_ID, 'DESC');

        if ($collection->count() == 0) {
            throw new \Exception("No record found in api record table({$quoteId}_$quoteItemId).");
        }

        $apiRecord = $collection->getFirstItem();

        $progressStatus = $apiRecord->getData(HotaiPointApiRecordModel::COMMIT_STATUS) == 1 ?
            Common::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_COMMIT :
            Common::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_MADE;

        $deductionPoint = $apiRecord->getData(HotaiPointApiRecordModel::DEDUCTION_POINT);

        $pointData = [
            "row_total_point_used" => $deductionPoint,
            "row_total_point_discount" => $this->calculateRowTotalPointDiscount($deductionPoint),
            "hotai_point_deduction_point_sync_from_quote_item_status" => 1,
            "hotai_point_deduction_point_trace_no" => $apiRecord->getData(HotaiPointApiRecordModel::TRACE_NO),
            "hotai_point_deduction_point_progress_status" => $progressStatus,
            "hotai_point_deduction_point_trans_s_n" => $apiRecord->getData(HotaiPointApiRecordModel::TRANS_S_N),
            "hotai_point_deduction_point_trans_datetime" => $apiRecord->getData(HotaiPointApiRecordModel::TRANS_DATETIME),
            "hotai_point_deduction_point_commit_or_cancel_fail_counter" => 0,
            "hotai_point_deduction_point_memo" => null,
        ];

        return $pointData;
    }

    public function checkDeductionPointDataAfterSync(int|string $orderItemId): bool
    {
        /** @var OrderItem $item */
        $item = $this->orderItemRepository->get($orderItemId);

        if (empty($item->getData("hotai_point_deduction_point_trace_no"))) {
            return false;
        }

        if (empty($item->getData("hotai_point_deduction_point_trans_s_n"))) {
            return false;
        }

        if (empty($item->getData("hotai_point_deduction_point_trans_datetime"))) {
            return false;
        }

        return true;
    }

    public function restoreDeductionPointDataFromApiRecordIfSyncIssue(OrderItem $item, Order $order): void
    {
        $quoteId     = $order->getQuoteId();
        $quoteItemId = $item->getQuoteItemId();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote     = $this->quoteRepository->get($quoteId);
        $quoteItem = $quote->getItemById($quoteItemId);

        if (is_null($quoteItem) || $quoteItem === false) {
            throw new \Exception("Can't find quote item while executing syncHotaiPointFieldsFromQuoteItem({$quoteId}).");
        }

        $collection = $this->hotaiPointApiRecordRepository->getCollectionByQuoteInfo($quoteId, $quoteItemId);
        $collection->setOrder(HotaiPointApiRecordModel::RECORD_ID, 'DESC');

        if ($collection->count() == 0) {
            throw new \Exception("Record count query from api record table not correct({$quoteId}_$quoteItemId).");
        }

        // 當有多筆紀錄時，以最新的紀錄作為同步對象（按 record_id 降序排序）
        $apiRecord = $collection->getFirstItem();
        $syncStatus        = 1;
        $progressStatus    = 1;
        $dataBeforeRestore = [
            "hotai_point_deduction_point_sync_from_quote_item_status" => $item->getData("hotai_point_deduction_point_sync_from_quote_item_status"),
            "hotai_point_deduction_point_trace_no"                    => $item->getData("hotai_point_deduction_point_trace_no"),
            "hotai_point_deduction_point_progress_status"             => $item->getData("hotai_point_deduction_point_progress_status"),
            "hotai_point_deduction_point_trans_s_n"                   => $item->getData("hotai_point_deduction_point_trans_s_n"),
            "hotai_point_deduction_point_trans_datetime"              => $item->getData("hotai_point_deduction_point_trans_datetime"),
        ];

        if (empty($item->getData("row_total_point_used"))) {
            $item->setData("row_total_point_used", $apiRecord->getData(HotaiPointApiRecordModel::DEDUCTION_POINT));
        }

        if (empty($item->getData("row_total_point_discount"))) {
            $item->setData("row_total_point_discount", $this->calculateRowTotalPointDiscount($apiRecord->getData(HotaiPointApiRecordModel::DEDUCTION_POINT)));
        }

        $item->setData(
            "hotai_point_deduction_point_sync_from_quote_item_status",
            $syncStatus
        );

        $item->setData(
            "hotai_point_deduction_point_trace_no",
            $apiRecord->getData(HotaiPointApiRecordModel::TRACE_NO)
        );

        $item->setData(
            "hotai_point_deduction_point_progress_status",
            $progressStatus
        );

        $item->setData(
            "hotai_point_deduction_point_trans_s_n",
            $apiRecord->getData(HotaiPointApiRecordModel::TRANS_S_N)
        );

        $item->setData(
            "hotai_point_deduction_point_trans_datetime",
            $apiRecord->getData(HotaiPointApiRecordModel::TRANS_DATETIME)
        );

        $currentMemo = $item->getData("hotai_point_deduction_point_memo") ?? $quoteItem->getData("hotai_point_deduction_point_memo");
        $memo        = $this->prepareMemoStringForUpdate(
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

        $item->setData(
            "hotai_point_deduction_point_memo",
            $memo
        );

        $this->orderItemRepository->save($item);
    }

    public function setPointCommitRetryDataBySalesOrderId(int|string $salesOrderId): void
    {
        /** @var Order $order */
        $order = $this->orderRepository->get((int)$salesOrderId);

        if (empty($order->getId())) {
            return;
        }

        $order->setData(DeductionPointRetryStatus::FIELD_NAME, DeductionPointRetryStatus::STATUS_NEED_RETRY);

        $this->orderRepository->save($order);
    }

    public function writeLogIfEnabled(string|array $message, string $folderName, string $logOptionValue): void
    {
        if (!HotaiCoreDebugLog::isEnable(self::DEBUG_MODULE_NAME, $logOptionValue)) {
            return;
        }

        $this->hotaiCoreCommonHelper->writeLog($message, $folderName);
    }
}
