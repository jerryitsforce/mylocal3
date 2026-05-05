<?php

namespace Branch8\Yoxi\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Api\BatchImportTicketFlowInterface;
use Branch8\Yoxi\Model\YoxiBatchSetting as BatchSetting;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\Collection as BatchSettingCollection;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\CollectionFactory as BatchSettingCollectionFactory;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\CollectionFactory;
use Branch8\Yoxi\Model\ResourceModel\YoxiTicketRecord\Collection;
use Branch8\Yoxi\Model\YoxiTicketRecord as Record;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\Yoxi\Helper\Api as ApiHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Reason as TicketReason;

class Flow implements BatchImportTicketFlowInterface
{
    const LOG_FOLDER_NAME = 'Yoxi/Flow';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var BatchSettingCollectionFactory */
    protected $batchSettingCollectionFactory;

    /** @var CustomerTicketCollectionFactory */
    protected $customerTicketCollectionFactory;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CollectionFactory $collectionFactory,
        BatchSettingCollectionFactory $batchSettingCollectionFactory,
        CustomerTicketCollectionFactory $customerTicketCollectionFactory,
        ApiHelper $apiHelper,
        OrderItemRepository $orderItemRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->collectionFactory               = $collectionFactory;
        $this->batchSettingCollectionFactory   = $batchSettingCollectionFactory;
        $this->customerTicketCollectionFactory = $customerTicketCollectionFactory;
        $this->apiHelper                       = $apiHelper;
        $this->orderItemRepository             = $orderItemRepository;
        $this->connection                      = $resourceConnection->getConnection();
    }

    public function getAvailableBatchData(int|string $productId): array
    {
        $batchData = [];

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $currentDatetime = $taiwanDateObj->format("Y-m-d H:i:s");

        $batchSettingIdFieldName     = BatchSetting::SETTING_ID;
        $recordTableName             = Record::TABLE_NAME;
        $recordBatchSettingFieldName = Record::BATCH_SETTING_ID;

        /** @var BatchSettingCollection $collection */
        $collection = $this->batchSettingCollectionFactory->create();
        $collection
            ->join(
                [Record::TABLE_NAME => Record::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$recordTableName}.{$recordBatchSettingFieldName}",
                [Record::RECORD_ID]
            )->addFieldToSelect(
                [
                    BatchSetting::SETTING_ID,
                    BatchSetting::BATCH_CODE,
                    BatchSetting::SALE_START_TIME,
                    BatchSetting::SALE_END_TIME,
                ]
            )->addFieldToFilter(
                "main_table." . BatchSetting::BELONG_TO_PRODUCT_ID,
                $productId
            )->addFieldToFilter(
                BatchSetting::SALE_START_TIME,
                ['lteq' => $currentDatetime]
            )->addFieldToFilter(
                BatchSetting::SALE_END_TIME,
                ['gteq' => $currentDatetime]
            )->addFieldToFilter(
                Record::STATUS,
                TicketStatus::STATUS_IMPORTED
            );

        $collection
            ->getSelect()
            ->columns([new \Zend_Db_Expr("COUNT(`{$batchSettingIdFieldName}`) as quantity")])
            ->group(
                "main_table.{$batchSettingIdFieldName}"
            );

        $batchData = [];
        /** @var BatchSetting $batchSetting */
        foreach ($collection->getItems() as $batchSetting) {
            $batchData[] = [
                'setting_id'      => $batchSetting->getSettingId(),
                'batch_code'      => $batchSetting->getBatchCode(),
                'sale_start_time' => $batchSetting->getSaleStartTime(),
                'sale_end_time'   => $batchSetting->getSaleEndTime(),
                'quantity'        => (int) $batchSetting->getData("quantity") ?? 0,
            ];
        }

        return $batchData;
    }

    public function cancelTickets(int $orderItemId): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title" => "Yoxi cancelTickets flow start.",
            "Order item ID" => $orderItemId,
        ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        $this->connection->beginTransaction();

        try {
            // 先針對預分配的票券紀錄做回復
            $salesOrderItem = $this->orderItemRepository->get($orderItemId);
            $quoteItemId    = $salesOrderItem->getQuoteItemId();

            $allocatedCollection = $this->collectionFactory->create();
            $allocatedCollection->addFieldToFilter(Record::QUOTE_ITEM_ID, $quoteItemId);
            $allocatedCollection->addFieldToFilter(Record::STATUS, TicketStatus::STATUS_ALLOCATED);
            $allocatedCollection->load();

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Yoxi cancelTickets flow resetAllocatedRecords query result.",
                "Order item ID" => $orderItemId,
                "Quote item ID" => $quoteItemId,
                "Target allocated records IDs" => $allocatedCollection->getAllIds(),
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            /** @var Record $record */
            foreach ($allocatedCollection->getItems() as $record) {
                $this->resetAllocatedRecords($record);
            }

            // 查詢目標票券序號紀錄
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter(Record::SALES_ORDER_ITEM_ID, $orderItemId);
            $collection->addFieldToFilter(Record::STATUS, ['nin' => [TicketStatus::STATUS_IMPORTED, TicketStatus::STATUS_ALLOCATED]]);
            $collection->load();

            $searchSerialNumbers = [];
            /** @var Record $record */
            foreach ($collection->getItems() as $record) {
                $searchSerialNumbers[] = $record->getSerialNumber();
            }

            // $apiResponse       = $this->apiHelper->requestApiGetCouponList($searchSerialNumbers);
            // $serialNumberArray = $this->apiHelper->getDecryptContentFromResponse($apiResponse);

            // 篩選出有殘值的票券
            // $handleSerialNumbers = [];
            // foreach ($serialNumberArray["CouponList"] as $serialumberData) {
            //     $serialCode      = $serialumberData["SerialCode"];
            //     $totalCount      = $serialumberData["TotalCount"];
            //     $usedCount       = $serialumberData["UsedCount"];
            //     $invalidateCount = $serialumberData["InvalidateCount"];

            //     if ($totalCount > ($usedCount + $invalidateCount)) {
            //         $handleSerialNumbers[] = $serialCode;
            //     }
            // }

            // 票券都沒有殘值, 直接commit transaction後結束
            // if (count($handleSerialNumbers) == 0) {
            //     $this->connection->commit();
            //     return;
            // }

            // 不用篩了, 全部都要拿去請求作廢API
            $handleSerialNumbers = $searchSerialNumbers;

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title" => "Yoxi cancelTickets flow requestApiInvalidateCoupon query result.",
                "Order item ID" => $orderItemId,
                "Target requestApiInvalidateCoupon ticket records IDs" => $collection->getAllIds(),
                "Target requestApiInvalidateCoupon serial numbers" => $handleSerialNumbers,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            if (count($handleSerialNumbers) > 0) {
                $apiResponse = $this->apiHelper->requestApiInvalidateCoupon($handleSerialNumbers);
                $decryptData = $this->apiHelper->getDecryptContentFromResponse($apiResponse);

                // $this->setTicketStatusToImported($collection, $decryptData);
                $this->setTicketStatusToReturned($collection, $decryptData);
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"         => "Something went wrong while executing Yoxi cancelTickets transaction.",
                "Message"       => $e->getMessage(),
                "Order item ID" => $orderItemId,
                "Last header"   => $this->apiHelper->getRequestHeader(),
                "Last request"  => $this->apiHelper->getRequestDataArray(),
                "Last response" => $this->apiHelper->getLastResponse()
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw $e;
        }
    }

    protected function resetAllocatedRecords(Record $record): void
    {
        $beforeStatus = $record->getStatus();
        $afterStatus  = TicketStatus::STATUS_IMPORTED;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $record->getData(Record::MEMO),
            [
                "Timestamp" => time(),
                "Datetime"  => date("Y-m-d H:i:s"),
                "Title"     => "Yoxi cancelTickets flow resetAllocatedRecords executed.",
                "Message"   => "Set allocated records back to imported, before status: {$beforeStatus}, after status: {$afterStatus}",
            ]
        );

        $updateData  = [
            Record::QUOTE_ITEM_ID       => null,
            Record::SALES_ORDER_ITEM_ID => null,
            Record::USE_START_TIME      => null,
            Record::USE_END_TIME        => null,
            Record::DUE_DAYS            => null,
            Record::USED_COUNT          => 0,
            Record::USED_DATE           => null,
            Record::STATUS              => $afterStatus,
            Record::MEMO                => $memoMessage,
        ];
        $whereUpdate = [
            Record::RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->update(
            Record::TABLE_NAME,
            $updateData,
            $whereUpdate
        );

        $updateData  = [
            CustomerTicket::STATUS => $afterStatus,
        ];
        $whereUpdate = [
            CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_YOXI_TICKET,
            CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->update(
            CustomerTicket::TABLE_NAME,
            $updateData,
            $whereUpdate
        );
    }

    protected function setTicketStatusToImported(Collection $collection, array $decryptData): void
    {
        foreach ($collection->getItems() as $record) {
            $beforeStatus = $record->getStatus();
            $afterStatus  = TicketStatus::STATUS_IMPORTED;

            $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $record->getData(Record::MEMO),
                [
                    "Timestamp"                 => time(),
                    "Datetime"                  => date("Y-m-d H:i:s"),
                    "Title"                     => "YOXI cancelTickets flow called, set status to STATUS_IMPORTED.",
                    "Message"                   => "Before status: {$beforeStatus}, after status: {$afterStatus}",
                    "API response decrypt data" => $decryptData
                ]
            );

            $updateData  = [
                Record::QUOTE_ITEM_ID       => null,
                Record::SALES_ORDER_ITEM_ID => null,
                Record::USE_START_TIME      => null,
                Record::USE_END_TIME        => null,
                Record::DUE_DAYS            => null,
                Record::USED_COUNT          => 0,
                Record::USED_DATE           => null,
                Record::STATUS              => $afterStatus,
                Record::MEMO                => $memoMessage,
            ];
            $whereUpdate = [
                Record::RECORD_ID . ' = ?' => $record->getId()
            ];
            $this->connection->update(
                Record::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            $whereDelete = [
                CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_YOXI_TICKET,
                CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
            ];
            $this->connection->delete(
                CustomerTicket::TABLE_NAME,
                $whereDelete
            );
        }
    }

    protected function setTicketStatusToReturned(Collection $collection, array $decryptData): void
    {
        foreach ($collection->getItems() as $record) {
            $beforeStatus = $record->getStatus();
            $afterStatus  = TicketStatus::STATUS_RETURNED;

            $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $record->getData(Record::MEMO),
                [
                    "Timestamp"                 => time(),
                    "Datetime"                  => date("Y-m-d H:i:s"),
                    "Title"                     => "YOXI cancelTickets flow called, set status to STATUS_RETURNED.",
                    "Message"                   => "Before status: {$beforeStatus}, after status: {$afterStatus}",
                    "API response decrypt data" => $decryptData
                ]
            );

            $updateData  = [
                Record::STATUS => $afterStatus,
                Record::MEMO   => $memoMessage,
            ];
            $whereUpdate = [
                Record::RECORD_ID . ' = ?' => $record->getId()
            ];
            $this->connection->update(
                Record::TABLE_NAME,
                $updateData,
                $whereUpdate
            );

            $updateData  = [
                CustomerTicket::STATUS => $afterStatus,
            ];
            $whereUpdate = [
                CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_YOXI_TICKET,
                CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
            ];
            $this->connection->update(
                CustomerTicket::TABLE_NAME,
                $updateData,
                $whereUpdate
            );
        }
    }

    public function getCurrentBatchSettingDataForCustomOption(int|string $productId): array
    {
        $collection = $this->batchSettingCollectionFactory->create();

        $collection->addFieldToFilter(BatchSetting::BELONG_TO_PRODUCT_ID, $productId);
        $collection->addFieldToSelect(
            [
                BatchSetting::SETTING_ID,
                BatchSetting::BATCH_CODE,
            ]
        );

        return $collection->getData();
    }

    public function checkIfQuantityEnoughByCustomOptionAndRequestQuantity(int|string $productId, string $customOptionValue, int|string $requestQuantity): array
    {
        $recordTableName = \Branch8\Yoxi\Model\YoxiTicketRecord::TABLE_NAME;
        $batchTableName = \Branch8\Yoxi\Model\YoxiBatchSetting::TABLE_NAME;

        $recordIdField = \Branch8\Yoxi\Model\YoxiTicketRecord::RECORD_ID;
        $batchSettingIdFieldInBatchTable = \Branch8\Yoxi\Model\YoxiBatchSetting::SETTING_ID;
        $batchSettingIdFieldInRecordTable = \Branch8\Yoxi\Model\YoxiTicketRecord::BATCH_SETTING_ID;
        $productIdField = \Branch8\Yoxi\Model\YoxiBatchSetting::BELONG_TO_PRODUCT_ID;
        $batchCodeField = \Branch8\Yoxi\Model\YoxiBatchSetting::BATCH_CODE;
        $batchSaleStartTimeField = \Branch8\Yoxi\Model\YoxiBatchSetting::SALE_START_TIME;
        $batchSaleEndTimeField = \Branch8\Yoxi\Model\YoxiBatchSetting::SALE_END_TIME;
        $statusField = \Branch8\Yoxi\Model\YoxiTicketRecord::STATUS;
        $targetStatus = \Branch8\HotaiCore\Model\Ticket\Status::STATUS_IMPORTED;

        $currentDatetimeObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
        $currentDatetime = $currentDatetimeObj->format("Y-m-d H:i:s");

        $select = $this->connection->select()
            ->from(
                $batchTableName,
                [
                    $batchSettingIdFieldInBatchTable,
                    $batchSaleStartTimeField,
                    $batchSaleEndTimeField
                ]
            )
            ->where("{$productIdField} = ?", $productId)
            ->where("{$batchCodeField} = ?", $customOptionValue);

        $resultArray = $this->connection->fetchAll($select);
        $result      = $resultArray[0] ?? null;

        if (empty($result)) {
            return [
                "result" => false,
                "reason" => TicketReason::REASON_FOR_OOS_CHECK_BATCH_SETTING_NOT_FOUND,
            ];
        }

        $start = new \DateTime($result[$batchSaleStartTimeField], new \DateTimeZone("Asia/Taipei"));
        $end = new \DateTime($result[$batchSaleEndTimeField], new \DateTimeZone("Asia/Taipei"));

        $isInSaleTimeWindow = $start <= $currentDatetimeObj && $currentDatetimeObj <= $end;

        if (!$isInSaleTimeWindow) {
            return [
                "result" => false,
                "reason" => TicketReason::REASON_FOR_OOS_CHECK_OUT_OF_SALE_TIME_WINDOW,
            ];
        }

        $select = $this->connection->select()
            ->from(
                $recordTableName,
                [$recordIdField]
            )
            ->joinLeft(
                $batchTableName,
                "{$recordTableName}.{$batchSettingIdFieldInRecordTable} = {$batchTableName}.{$batchSettingIdFieldInBatchTable}",
                []
            )
            ->where("{$statusField} = ?", $targetStatus)
            ->where("{$batchTableName}.{$productIdField} = ?", $productId)
            ->where("{$batchTableName}.{$batchCodeField} = ?", $customOptionValue)
            ->where("{$batchTableName}.{$batchSaleStartTimeField} <= ?", $currentDatetime)
            ->where("{$batchTableName}.{$batchSaleEndTimeField} >= ?", $currentDatetime)
            ->limit($requestQuantity);

        $result = $this->connection->fetchAll($select);

        $quantity = count($result);

        if ($quantity === 0) {
            return [
                "result" => false,
                "reason" => TicketReason::REASON_FOR_OOS_CHECK_BATCH_SETTING_EXIST_BUT_ZERO_QUANTITY_LEFT,
            ];
        }

        $quantityCheck = $quantity >= $requestQuantity;

        if (!$quantityCheck) {
            return [
                "result" => false,
                "reason" => TicketReason::REASON_FOR_OOS_CHECK_QUANTITY_NOT_ENOUGH,
            ];
        }

        return [
            "result" => true,
            "reason" => null,
        ];
    }
}
