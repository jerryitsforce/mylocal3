<?php

namespace Branch8\ReturnPointForRefundOrder\Cron;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiPoint\Helper\Api as HotaiPointApi;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\DebugLog as HotaiCoreDebugLog;

class ReturnPoint
{
    private const DEBUG_MODULE_NAME = 'branch8_returnpointforrefundorder';
    private const DEBUG_LOG_OPTION = 'return_point_cron';
    const LOG_FOLDER_NAME = 'ReturnPointForRefundOrder/Cron/ReturnPoint';

    const RETRY_COUNT_LIMIT = 3;
    const RETRY_DAY_LIMIT   = 2;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var OrderItemRepository */
    private $orderItemRepository;

    /** @var HotaiPointApi */
    protected $hotaiPointApi;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    protected $walkthroughLog = [];

    public function __construct(
        ResourceConnection $resourceConnection,
        OrderItemRepository $orderItemRepository,
        HotaiPointApi $hotaiPointApi,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->resourceConnection    = $resourceConnection;
        $this->orderItemRepository   = $orderItemRepository;
        $this->hotaiPointApi         = $hotaiPointApi;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    public function execute()
    {
        $targetArray = $this->getTargetArray();
        $this->writeLogIfEnabled(
            'ReturnPoint cron start, target count: ' . count($targetArray)
        );

        foreach ($targetArray as $memoItem) {
            $this->walkthroughLog = [];

            try {
                $orderItem = null;

                if ($memoItem["refund_point"] == 0) {
                    $this->updateCreditMemoItem(
                        (int) $memoItem["memo_item_id"],
                        ['refund_point_status' => 1]
                    );

                    continue;
                }

                $orderItem = $this->orderItemRepository->get((int) $memoItem["order_item_id"]);

                if (!$this->checkOrderItemData($orderItem)) {
                    $this->writeLogIfEnabled(json_encode([
                        "Title"                                      => "CheckOrderItemData return false.",
                        "Order item ID"                              => $orderItem->getId(),
                        "refund_point"                               => $memoItem["refund_point"] ?? 0,
                        "row_total_point_used"                       => $orderItem->getData("row_total_point_used"),
                        "hotai_point_deduction_point_trace_no"       => $orderItem->getData("hotai_point_deduction_point_trace_no"),
                        "hotai_point_deduction_point_trans_s_n"      => $orderItem->getData("hotai_point_deduction_point_trans_s_n"),
                        "hotai_point_deduction_point_trans_datetime" => $orderItem->getData("hotai_point_deduction_point_trans_datetime"),
                    ], \JSON_UNESCAPED_SLASHES));

                    throw new \Exception("order item data for return point error.");
                }

                $getPointResponse       = $this->hotaiPointApi->requestApiGetPointByOneid((int) $orderItem->getOrder()->getCustomerId());
                $pointBeforeReturn      = $this->hotaiPointApi->getPointFromResponse($getPointResponse);
                $returnFailurePointInfo = $this->hotaiPointApi->getReturnFailurePointInfoByOrderItemId((int) $orderItem->getId());

                $this->walkthroughLog[] = "Ready to request ReturnPoint API.";
                $returnPointResponse    = $this->hotaiPointApi->requestApiReturnPointByOrderItemId(
                    (int) $orderItem->getId(),
                    [
                        $this->hotaiPointApi::API_RESPONSE_CODE_SUCCESS,
                        $this->hotaiPointApi::API_RESPONSE_CODE_TRANSACTION_LOCK
                    ]
                );
                $returnPointRequest     = $this->hotaiPointApi->getRequestDataArray();
                $this->walkthroughLog[] = "ReturnPoint API done, request data: " . $this->hotaiPointApi->getRequestDataString();
                $this->walkthroughLog[] = "ReturnPoint API done, response data: " . json_encode($returnPointResponse);

                // 判斷commitResponse是否為transaction lock
                $returnCode = $this->hotaiPointApi->getReturnCodeFromResponse($returnPointResponse);
                if ($this->checkIsTransactionLock($returnCode)) {
                    $this->walkthroughLog[] = "ReturnPoint API result is transaction lock, extra handle start.";

                    // 如果是lock則請求解除API
                    $this->walkthroughLog[] = "Ready to request Unlock API.";
                    $unlockResponse         = $this->hotaiPointApi->requestApiUnlockOneId((int) $orderItem->getOrder()->getCustomerId());
                    $this->walkthroughLog[] = "Unlock API done, request data: " . $this->hotaiPointApi->getRequestDataString();
                    $this->walkthroughLog[] = "Unlock API done, response data: " . json_encode($unlockResponse);

                    // 然後再次commit, 這次預期一定要成功, 所以不傳遞額外allow return code array
                    $this->walkthroughLog[] = "Ready to request ReturnPoint API again after Unlock API request is done.";
                    $returnPointResponse    = $this->hotaiPointApi->requestApiReturnPointByOrderItemId((int) $orderItem->getId());
                    $this->walkthroughLog[] = "ReturnPoint API again done, request data: " . $this->hotaiPointApi->getRequestDataString();
                    $this->walkthroughLog[] = "ReturnPoint API again done, response data: " . json_encode($returnPointResponse);
                }

                $returnPointSuccessTaiwanDateObj = $this->getTaiwanDateObject();

                $returnPointTraceNo = $this->hotaiPointApi->getTraceNoFromResponse($returnPointResponse);
                $pointAfterReturn   = $this->hotaiPointApi->getPointFromResponse($returnPointResponse);
                $pointDescription   = "Point before return: {$pointBeforeReturn}, point after return: {$pointAfterReturn}";

                $returnPointMemo = [
                    "Title"                 => "ReturnPoint cron API success.",
                    "Taiwan Datetime"       => $returnPointSuccessTaiwanDateObj->format("Y-m-d H:i:s"),
                    "Return point request"  => $returnPointRequest,
                    "Return point response" => $returnPointResponse,
                    "Point description"     => $pointDescription,
                    "Walkthrough log"       => $this->walkthroughLog
                ];

                $creditmemoItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                    $memoItem["memo_item_description"] ?? "",
                    $returnPointMemo
                );

                $orderItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                    $orderItem->getData("hotai_point_deduction_point_memo") ?? "",
                    $returnPointMemo
                );

                $this->updateCreditMemoItemAndOrderItem(
                    (int) $memoItem["memo_item_id"],
                    [
                        'description'         => $creditmemoItemDescription,
                        'refund_point_status' => 1,
                        "refunded_point"      => $returnFailurePointInfo["nonFailurePoint"] ?? 0
                    ],
                    (int) $orderItem->getId(),
                    [
                        'hotai_point_return_point_trace_no'       => $returnPointTraceNo,
                        'hotai_point_return_point_trans_datetime' => $returnPointSuccessTaiwanDateObj->format("Y-m-d H:i:s"),
                        'hotai_point_deduction_point_memo'        => $orderItemDescription
                    ]
                );

                $this->writeLogIfEnabled(json_encode([
                    'Title' => 'ReturnPoint cron item handled successfully.',
                    'Memo item ID' => (int) $memoItem['memo_item_id'],
                    'Order item ID' => (int) $orderItem->getId(),
                    'Walkthrough log' => $this->walkthroughLog,
                ], \JSON_UNESCAPED_SLASHES));
            } catch (\Exception $e) {
                $this->writeLogIfEnabled(json_encode([
                    "Title"          => "Exception.",
                    "Message"        => $e->getMessage(),
                    "Memo item data" => $memoItem,
                ], \JSON_UNESCAPED_SLASHES));

                $taiwanDateObj = $this->getTaiwanDateObject();

                $newDesciptionArray = [
                    "Title"           => "ReturnPoint cron exception.",
                    "Taiwan Datetime" => $taiwanDateObj->format("Y-m-d H:i:s"),
                    "Message"         => $e->getMessage(),
                    "Walkthrough log" => $this->walkthroughLog
                ];

                $description = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                    $memoItem["memo_item_description"] ?? "",
                    $newDesciptionArray
                );

                $newRetryCount = $memoItem["refund_point_retry_count"] + 1;

                $updateData = [
                    'description'              => $description,
                    'refund_point_retry_count' => $newRetryCount,
                ];

                if ($newRetryCount >= self::RETRY_COUNT_LIMIT) {
                    $updateData["refund_point_status"] = -1;
                }

                if (!empty($orderItem)) {
                    $orderItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                        $orderItem->getData("hotai_point_deduction_point_memo") ?? "",
                        $newDesciptionArray
                    );

                    $this->updateCreditMemoItemAndOrderItem(
                        (int) $memoItem["memo_item_id"],
                        $updateData,
                        (int) $orderItem->getId(),
                        [
                            'hotai_point_deduction_point_memo' => $orderItemDescription
                        ]
                    );
                } else {
                    $this->updateCreditMemoItem(
                        (int) $memoItem["memo_item_id"],
                        $updateData
                    );
                }
            }
        }

        $this->writeLogIfEnabled('ReturnPoint cron end.');
    }

    protected function getTargetArray()
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from('sales_creditmemo')
            ->join(
                ['sales_creditmemo_item' => 'sales_creditmemo_item'],
                'sales_creditmemo.entity_id = sales_creditmemo_item.parent_id',
                [
                    'memo_item_id'             => 'entity_id',
                    'order_item_id'            => 'order_item_id',
                    'refund_point'             => 'refund_point',
                    'refunded_point'           => 'refunded_point',
                    'refund_point_status'      => 'refund_point_status',
                    'refund_point_retry_count' => 'refund_point_retry_count',
                    'memo_item_description'    => 'description',
                ]
            );

        // 搜尋條件1
        // 發票狀態已完成, 退點狀態未完成, 退點嘗試次數 = 0
        $select->where('is_invoice_success = 1 AND sales_creditmemo_item.refund_point_status = 0 AND sales_creditmemo_item.refund_point_retry_count = 0');

        // 搜尋條件2
        // 發票狀態已完成, 退點狀態未完成, 退點嘗試次數 > 0, 建立時間N天內(見開頭const)
        // 簡單來說, 對於失敗再次嘗試的退點動作多了N天內的限制
        $select->orWhere('is_invoice_success = 1 AND sales_creditmemo_item.refund_point_status = 0 AND sales_creditmemo_item.refund_point_retry_count > 0 AND sales_creditmemo.created_at >= NOW() - INTERVAL ' . self::RETRY_DAY_LIMIT . ' DAY');

        return $connection->fetchAll($select);
    }

    protected function updateCreditMemoItem(int $memoItemId, array $memoItemData)
    {
        $connection = $this->resourceConnection->getConnection();

        $table = $connection->getTableName('sales_creditmemo_item');

        try {
            $connection->beginTransaction();

            $connection->update(
                $table,
                $memoItemData,
                ['entity_id = ?' => $memoItemId]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function updateCreditMemoItemAndOrderItem(int $memoItemId, array $memoItemData, int $orderItemId, array $orderItemData)
    {
        $connection = $this->resourceConnection->getConnection();

        try {
            $connection->beginTransaction();

            $table = $connection->getTableName('sales_creditmemo_item');

            $connection->update(
                $table,
                $memoItemData,
                ['entity_id = ?' => $memoItemId]
            );

            $table = $connection->getTableName('sales_order_item');

            $connection->update(
                $table,
                $orderItemData,
                ['item_id = ?' => $orderItemId]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function checkOrderItemData(OrderItem $orderItem): bool
    {
        if (empty($orderItem->getData("hotai_point_deduction_point_trace_no"))) {
            return false;
        }

        if (empty($orderItem->getData("hotai_point_deduction_point_trans_s_n"))) {
            return false;
        }

        if (empty($orderItem->getData("hotai_point_deduction_point_trans_datetime"))) {
            return false;
        }

        return true;
    }

    protected function getTaiwanDateObject(): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $taiwanDateObj;
    }

    protected function checkIsTransactionLock(string $returnCode): bool
    {
        return $returnCode == $this->hotaiPointApi::API_RESPONSE_CODE_TRANSACTION_LOCK;
    }

    protected function writeLogIfEnabled(string|array $message): void
    {
        if (!HotaiCoreDebugLog::isEnable(self::DEBUG_MODULE_NAME, self::DEBUG_LOG_OPTION)) {
            return;
        }

        $this->hotaiCoreCommonHelper->writeLog($message, self::LOG_FOLDER_NAME);
    }
}
