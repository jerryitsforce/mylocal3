<?php

namespace Branch8\GeneralNonNotifyTicket\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\GeneralNonNotifyTicket\Model\Config\Source\LogOption;
use Branch8\HotaiCore\Api\BatchImportTicketFlowInterface;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting as BatchSetting;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketBatchSetting\Collection as BatchSettingCollection;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketBatchSetting\CollectionFactory as BatchSettingCollectionFactory;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord\CollectionFactory;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketRecord\Collection;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord as Record;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Framework\App\ResourceConnection;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Reason as TicketReason;

class Flow implements BatchImportTicketFlowInterface
{
    const LOG_FOLDER_NAME = 'GeneralNonNotifyTicket/Flow';

    private const DEBUG_LOG_OPTION = LogOption::LOG_FLOW;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var Common */
    protected $commonHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var BatchSettingCollectionFactory */
    protected $batchSettingCollectionFactory;

    /** @var CustomerTicketCollectionFactory */
    protected $customerTicketCollectionFactory;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        Common $commonHelper,
        CollectionFactory $collectionFactory,
        BatchSettingCollectionFactory $batchSettingCollectionFactory,
        CustomerTicketCollectionFactory $customerTicketCollectionFactory,
        OrderItemRepository $orderItemRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->commonHelper                    = $commonHelper;
        $this->collectionFactory               = $collectionFactory;
        $this->batchSettingCollectionFactory   = $batchSettingCollectionFactory;
        $this->customerTicketCollectionFactory = $customerTicketCollectionFactory;
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
        $this->connection->beginTransaction();

        try {
            // 先針對預分配的票券紀錄做回復
            $salesOrderItem = $this->orderItemRepository->get($orderItemId);
            $quoteItemId    = $salesOrderItem->getQuoteItemId();

            $allocatedCollection = $this->collectionFactory->create();
            $allocatedCollection->addFieldToFilter(Record::QUOTE_ITEM_ID, $quoteItemId);
            $allocatedCollection->addFieldToFilter(Record::STATUS, TicketStatus::STATUS_ALLOCATED);
            $allocatedCollection->load();

            /** @var Record $record */
            foreach ($allocatedCollection->getItems() as $record) {
                $this->resetAllocatedRecords($record);
            }
            // -------------------------

            // 篩選出"未使用"的票券
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter(Record::SALES_ORDER_ITEM_ID, $orderItemId);
            $collection->addFieldToFilter(Record::STATUS, TicketStatus::STATUS_UNUSED);
            $collection->load();

            // $this->setTicketStatusToImported($collection);
            $this->setTicketStatusToReturned($collection);

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    "Title"   => "Something went wrong while executing GeneralNonNotify cancelTickets transaction.",
                    "Message" => $e->getMessage()
                ], \JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

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
                "Title"     => "GeneralNonNotifyTicket cancelTickets flow resetAllocatedRecords executed.",
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
            CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET,
            CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->update(
            CustomerTicket::TABLE_NAME,
            $updateData,
            $whereUpdate
        );
    }

    protected function setTicketStatusToImported(Collection $collection): void
    {
        foreach ($collection->getItems() as $record) {
            $beforeStatus = $record->getStatus();
            $afterStatus  = TicketStatus::STATUS_IMPORTED;

            $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $record->getData(Record::MEMO),
                [
                    "Timestamp" => time(),
                    "Datetime"  => date("Y-m-d H:i:s"),
                    "Title"     => "GeneralNonNotifyTicket cancelTickets flow called, set status to STATUS_IMPORTED.",
                    "Message"   => "Before status: {$beforeStatus}, after status: {$afterStatus}",
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
                CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET,
                CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
            ];
            $this->connection->delete(
                CustomerTicket::TABLE_NAME,
                $whereDelete
            );
        }
    }

    protected function setTicketStatusToReturned(Collection $collection): void
    {
        foreach ($collection->getItems() as $record) {
            $beforeStatus = $record->getStatus();
            $afterStatus  = TicketStatus::STATUS_RETURNED;

            $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $record->getData(Record::MEMO),
                [
                    "Timestamp" => time(),
                    "Datetime"  => date("Y-m-d H:i:s"),
                    "Title"     => "GeneralNonNotifyTicket cancelTickets flow called, set status to STATUS_RETURNED.",
                    "Message"   => "Before status: {$beforeStatus}, after status: {$afterStatus}",
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
                CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET,
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
        $recordTableName = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord::TABLE_NAME;
        $batchTableName = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting::TABLE_NAME;

        $recordIdField = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord::RECORD_ID;
        $batchSettingIdFieldInBatchTable = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting::SETTING_ID;
        $batchSettingIdFieldInRecordTable = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord::BATCH_SETTING_ID;
        $productIdField = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting::BELONG_TO_PRODUCT_ID;
        $batchCodeField = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting::BATCH_CODE;
        $batchSaleStartTimeField = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting::SALE_START_TIME;
        $batchSaleEndTimeField = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting::SALE_END_TIME;
        $statusField = \Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord::STATUS;
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

        $result = $resultArray[0] ?? null;

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
