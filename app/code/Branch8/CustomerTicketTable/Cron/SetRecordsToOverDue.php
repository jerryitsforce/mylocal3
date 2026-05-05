<?php

namespace Branch8\CustomerTicketTable\Cron;

use Branch8\CustomerTicketTable\Helper\Logger as CustomerTicketTableLogger;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\Collection;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;

class SetRecordsToOverDue
{
    public const CLASS_KEY = 'SetRecordsToOverDue';

    /** @var CustomerTicketTableLogger */
    protected CustomerTicketTableLogger $logger;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var AdapterInterface */
    protected $connection;

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var EventManager */
    protected $eventManager;

    protected $handledItemIds   = [];
    protected $orderIdsForEvent = [];
    protected $exceptionArray   = [];

    /**
     * @param CustomerTicketTableLogger $logger
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param EventManager $eventManager
     */
    public function __construct(
        CustomerTicketTableLogger $logger,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        EventManager $eventManager
    ) {
        $this->logger                    = $logger;
        $this->collectionFactory          = $collectionFactory;
        $this->connection                 = $resourceConnection->getConnection();
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->eventManager               = $eventManager;
    }

    /**
     * Execute the cron.
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->log('SetRecordsToOverDue cron start.', self::CLASS_KEY);

        try {
            $collection = $this->getTargetCollection();

            $this->logger->log(
                'Ready to handle records count: ' . $collection->getSize() . '.',
                self::CLASS_KEY
            );

            if ($collection->getSize() > 0) {
                $allIds = implode(',', $collection->getAllIds());

                $this->logger->log('Ready to handle customer_ticket ids: ' . $allIds . '.', self::CLASS_KEY);

                $this->updateCollectionInTransaction($collection);

                $this->fireEventAfterOverDueHandleIfNeeded();
            }

            if (count($this->exceptionArray) > 0) {
                throw new \Exception(
                    "SetRecordsToOverDue cron has exception: " . json_encode($this->exceptionArray)
                );
            }
        } catch (\Exception $e) {
            $this->logger->logException($e, self::CLASS_KEY, ['message' => 'Cron execute failed.']);

            throw $e;
        }

        $this->logger->log('SetRecordsToOverDue cron ends.', self::CLASS_KEY);
    }

    /**
     * Get target CustomerTicket collection for overdue update.
     *
     * @return Collection
     */
    protected function getTargetCollection(): Collection
    {
        $collection = $this->collectionFactory->create();

        // 2024-11-08 "used" ticket stays at "used" status even if they are over due,
        // only "unused" ticket should be set to "over due" status.
        $collection->addFieldToFilter(
            CustomerTicket::STATUS,
            // ['in' => [TicketStatus::STATUS_UNUSED, TicketStatus::STATUS_USED]]
            ['in' => [TicketStatus::STATUS_UNUSED]]
        );

        $collection->addFieldToFilter(
            CustomerTicket::USE_END_TIME,
            ['lt' => $this->getTaiwanCurrentDatetime()]
        );

        return $collection;
    }

    /**
     * Get current datetime in Taiwan timezone.
     *
     * @return string
     */
    protected function getTaiwanCurrentDatetime(): string
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $taiwanDateObj->format("Y-m-d H:i:s");
    }

    /**
     * Update collection records in DB transactions.
     *
     * @param Collection $collection
     * @return void
     */
    protected function updateCollectionInTransaction(Collection $collection): void
    {
        /** @var CustomerTicket $customerTicket */
        foreach ($collection as $customerTicket) {
            try {
                $this->connection->beginTransaction();

                $updateData = [
                    'status' => TicketStatus::STATUS_OVER_DUE,
                ];

                $ticketTablePrimaryKey = ($customerTicket->getTicketTableName() == 'ticket_event_ticket') ? 'entity_id' : 'record_id';

                $whereUpdate = [
                    $ticketTablePrimaryKey . ' = ?' => $customerTicket->getTicketTableRecordId()
                ];

                $this->connection->update(
                    $customerTicket->getTicketTableName(),
                    $updateData,
                    $whereUpdate
                );

                // $customerTicketMemo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                //     CustomerTicket::MEMO,
                //     [
                //         "Title"         => "Update ticket status to over due by SetRecordsToOverDue cron.",
                //         "Timestamp"     => time(),
                //         "Datetime"      => date("Y-m-d H:i:s"),
                //         "Before status" => $customerTicket->getStatus()
                //     ]
                // );

                $updateData  = [
                    CustomerTicket::STATUS => TicketStatus::STATUS_OVER_DUE,
                    // CustomerTicket::MEMO   => $customerTicketMemo
                ];
                $whereUpdate = [
                    CustomerTicket::RECORD_ID . ' = ?' => $customerTicket->getId()
                ];

                $this->connection->update(
                    CustomerTicket::TABLE_NAME,
                    $updateData,
                    $whereUpdate
                );

                $this->connection->commit();

                // there might be records in customer_ticket table don't have sales_order_item_id in the future.
                // customer gets ticket as a gift, not buys it from order.
                if ($customerTicket->getSalesOrderItemId()) {
                    $this->handledItemIds[] = $customerTicket->getSalesOrderItemId();
                }
            } catch (\Exception $e) {
                $this->connection->rollBack();

                $this->exceptionArray[] = $e->getMessage();

                $this->logger->logException(
                    $e,
                    self::CLASS_KEY,
                    [
                        'message' => 'Error updating record in transaction.',
                        'customer_ticket_id' => (int) $customerTicket->getId(),
                        'ticket_table' => (string) $customerTicket->getTicketTableName(),
                        'ticket_table_record_id' => (int) $customerTicket->getTicketTableRecordId(),
                    ]
                );
            }
        }
    }

    /**
     * Dispatch events after overdue handling when needed.
     *
     * @return void
     */
    protected function fireEventAfterOverDueHandleIfNeeded()
    {
        if (count($this->handledItemIds) == 0) {
            return;
        }

        $this->handledItemIds = array_unique($this->handledItemIds);

        $this->logger->log(
            [
                'message' => 'Order item ids ready for CHECK_TICKET_ORDER_FOR_OVER_DUE_CRON after unique.',
                'order_item_ids' => $this->handledItemIds,
            ],
            self::CLASS_KEY
        );

        $collection = $this->orderItemCollectionFactory->create();
        $collection->addFieldToFilter('item_id', ['in' => $this->handledItemIds]);

        /** @var OrderItem $item */
        foreach ($collection as $item) {
            $this->orderIdsForEvent[] = $item->getOrderId();
        }

        $this->logger->log(
            [
                'message' => 'Order ids ready for CHECK_TICKET_ORDER_FOR_OVER_DUE_CRON.',
                'order_ids' => $this->orderIdsForEvent,
            ],
            self::CLASS_KEY
        );

        foreach ($this->orderIdsForEvent as $orderId) {
            $this->eventManager->dispatch(EventName::CHECK_TICKET_ORDER_FOR_OVER_DUE_CRON, [
                "orderId" => $orderId
            ]);
        }
    }
}
