<?php

namespace Branch8\FamilyBonusPin\Observer;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\FamilyBonusPin\Helper\Common as CommonHelper;
use Branch8\FamilyBonusPin\Model\Config\Source\LogOption;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\Collection;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinTicketRecord\CollectionFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as TicketRecordModel;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResourceConnection;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as BatchSettingModel;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Product\VirtualProductType;

class OrderChangeToCanceled implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'FamilyBonusPin/Observer/OrderChangeToCanceled';

    private const DEBUG_LOG_OPTION = LogOption::LOG_ORDER_CHANGE_TO_CANCELED;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    protected $eventName;
    protected $order;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        CollectionFactory $collectionFactory,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        Transaction $transaction,
        ResourceConnection $resourceConnection
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->commonHelper          = $commonHelper;
        $this->collectionFactory     = $collectionFactory;
        $this->orderRepository       = $orderRepository;
        $this->orderItemRepository   = $orderItemRepository;
        $this->transaction           = $transaction;
        $this->connection            = $resourceConnection->getConnection();
    }

    public function execute(Observer $observer)
    {
        $salesOrderItemIds = [];
        $order             = $observer->getOrder();
        $this->order       = $order;

        if (!$this->checkIfOrderStatusChangeToCanceled($order)) {
            return;
        }

        foreach ($order->getAllVisibleItems() as $item) {
            if (!$this->commonHelper->IsFamilyBonusPinTicketProduct($item->getProductId())) {
                continue;
            }

            $salesOrderItemIds[] = $item->getId();
        }

        if (count($salesOrderItemIds) == 0) {
            return;
        }

        $this->updateTicketRecordsFlow($salesOrderItemIds);
    }

    /**
     * 確認當前observer的訂單狀態變化是否符合要處理的條件
     *
     * @param Order $order
     * @return boolean
     */
    protected function checkIfOrderStatusChangeToCanceled(Order $order): bool
    {
        if ($order->getOrigData('status') == $order->getStatus()) {
            return false;
        }

        return $order->getStatus() == Status::STATUS_CANCELED;
    }

    /**
     * 根據傳入的sales order item IDs更新對應的票券紀錄
     * 1. 以sales order item ID找到quote item ID
     * 2. 以quote item ID找到票券紀錄
     * 3. 更新票券紀錄的sales order item ID和"已售出"狀態
     *
     * @param array $salesOrderItemIds
     * @return void
     */
    private function updateTicketRecordsFlow(array $salesOrderItemIds): void
    {
        $this->connection->beginTransaction();

        try {
            foreach ($salesOrderItemIds as $salesOrderItemId) {
                $salesOrderItem = $this->orderItemRepository->get($salesOrderItemId);
                $quoteItemId    = $salesOrderItem->getQuoteItemId();

                $collection = $this->getTicketCollectionByQuoteItemId($quoteItemId);

                /** @var TicketRecordModel $record */
                foreach ($collection->getItems() as $record) {
                    // $this->prepareUpdateTransactionToImported($record);
                    $this->prepareUpdateTransactionToReturned($record);
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    "Title"   => "Something went wrong while executing updateTicketRecordsFlow transaction.",
                    "Message" => $e->getMessage()
                ], \JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );

            throw $e;
        }
    }

    /**
     * 利用傳入的quote_item ID找出目標票券紀錄
     * 查詢條件
     * quote_item_id
     * status in (TicketRecordModel::STATUS_ALLOCATED, TicketRecordModel::STATUS_SOLD)
     * @param integer $quoteItemId
     * @return Collection
     */
    private function getTicketCollectionByQuoteItemId(int $quoteItemId): Collection
    {
        $batchSettingIdFieldName = TicketRecordModel::BATCH_SETTING_ID;
        $batchSettingTableName   = BatchSettingModel::TABLE_NAME;
        $settingIdFieldName      = BatchSettingModel::SETTING_ID;

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $collection
            ->join(
                [BatchSettingModel::TABLE_NAME => BatchSettingModel::TABLE_NAME],
                "main_table.{$batchSettingIdFieldName} = {$batchSettingTableName}.{$settingIdFieldName}",
                [
                    'batch_use_start_time' => 'use_start_time',
                    'batch_use_end_time'   => 'use_end_time',
                    'batch_due_days'       => 'due_days'
                ]
            );

        $collection->addFieldToFilter(TicketRecordModel::QUOTE_ITEM_ID, $quoteItemId);
        $collection->addFieldToFilter(TicketRecordModel::STATUS, [
            'in' => [TicketRecordModel::STATUS_ALLOCATED, TicketRecordModel::STATUS_SOLD]
        ]);

        $collection->load();

        return $collection;
    }

    /**
     * 準備更新票券表紀錄, (可能)刪除用戶持有表紀錄的transaction
     * @param TicketRecordModel $record
     * @return TicketRecordModel
     */
    private function prepareUpdateTransactionToImported(TicketRecordModel $record): void
    {
        $beforeStatus = $record->getStatus();
        $afterStatus  = \Branch8\HotaiCore\Model\Ticket\Status::STATUS_IMPORTED;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $record->getData(TicketRecordModel::MEMO),
            [
                "Timestamp" => time(),
                "Datetime"  => date("Y-m-d H:i:s"),
                "Title"     => "FamilyBonusPin OrderChangeToCanceled triggered.",
                "Message"   => "Set status back to imported, before status: {$beforeStatus}, after status: {$afterStatus}",
            ]
        );

        $updateData  = [
            TicketRecordModel::QUOTE_ITEM_ID       => null,
            TicketRecordModel::SALES_ORDER_ITEM_ID => null,
            TicketRecordModel::USE_START_TIME      => null,
            TicketRecordModel::USE_END_TIME        => null,
            TicketRecordModel::DUE_DAYS            => null,
            TicketRecordModel::STATUS              => $afterStatus,
            TicketRecordModel::MEMO                => $memoMessage,
        ];
        $whereUpdate = [
            TicketRecordModel::RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->update(
            TicketRecordModel::TABLE_NAME,
            $updateData,
            $whereUpdate
        );

        $whereDelete = [
            CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET,
            CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->delete(
            CustomerTicket::TABLE_NAME,
            $whereDelete
        );
    }

    private function prepareUpdateTransactionToReturned(TicketRecordModel $record): void
    {
        $beforeStatus = $record->getStatus();
        $afterStatus  = \Branch8\HotaiCore\Model\Ticket\Status::STATUS_RETURNED;

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $record->getData(TicketRecordModel::MEMO),
            [
                "Timestamp" => time(),
                "Datetime"  => date("Y-m-d H:i:s"),
                "Title"     => "FamilyBonusPin OrderChangeToCanceled triggered.",
                "Message"   => "Set status to returned, before status: {$beforeStatus}, after status: {$afterStatus}",
            ]
        );

        $updateData  = [
            TicketRecordModel::STATUS => $afterStatus,
            TicketRecordModel::MEMO   => $memoMessage,
        ];
        $whereUpdate = [
            TicketRecordModel::RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->update(
            TicketRecordModel::TABLE_NAME,
            $updateData,
            $whereUpdate
        );

        $updateData  = [
            CustomerTicket::STATUS => $afterStatus,
        ];
        $whereUpdate = [
            CustomerTicket::TYPE . ' = ?' => VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET,
            CustomerTicket::TICKET_TABLE_RECORD_ID . ' = ?' => $record->getId()
        ];
        $this->connection->update(
            CustomerTicket::TABLE_NAME,
            $updateData,
            $whereUpdate
        );
    }
}
