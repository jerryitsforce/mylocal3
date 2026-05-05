<?php

namespace Branch8\CustomerTicketTable\Cron;

use Branch8\CustomerTicketTable\Helper\Logger as CustomerTicketTableLogger;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\TicketOrderStatusChangeObserver\Helper\Common as TicketOrderHelper;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class CheckVirtualOrderTicketStatus
{
    public const CLASS_KEY = 'CheckVirtualOrderTicketStatus';
    public const HANDLE_ORDER_LIMIT_PER_CRON = 100;

    /** @var CustomerTicketTableLogger */
    protected CustomerTicketTableLogger $logger;

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var TicketOrderHelper */
    protected $ticketOrderHelper;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var EventManager */
    protected $eventManager;

    protected $processedOrderIds = [];
    protected $processedItemIds = [];

    /**
     * @param CustomerTicketTableLogger $logger
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param CustomerTicketRepository $customerTicketRepository
     * @param TicketOrderHelper $ticketOrderHelper
     * @param OrderRepository $orderRepository
     * @param EventManager $eventManager
     */
    public function __construct(
        CustomerTicketTableLogger $logger,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        CustomerTicketRepository $customerTicketRepository,
        TicketOrderHelper $ticketOrderHelper,
        OrderRepository $orderRepository,
        EventManager $eventManager
    ) {
        $this->logger = $logger;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->ticketOrderHelper = $ticketOrderHelper;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * Execute the cron.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->log('------Start Of Cron-CheckVirtualOrderTicketStatus-----', self::CLASS_KEY);

        try {
            $itemCollection = $this->getTargetOrderItemCollection();
            $this->logger->log(
                'Found ' . $itemCollection->getSize() . ' virtual order items to check.',
                self::CLASS_KEY
            );

            $processedCount = 0;
            $processedItemIds = [];
            foreach ($itemCollection as $item) {
                try {
                    $itemId = $item->getId();
                    // Skip if already processed (additional safety check)
                    if (in_array($itemId, $processedItemIds)) {
                        continue;
                    }
                    $processedItemIds[] = $itemId;
                    
                    $this->processOrderItem($item);
                    $processedCount++;
                } catch (\Exception $e) {
                    $this->logger->logException(
                        $e,
                        self::CLASS_KEY,
                        ['item_id' => (int) $item->getId(), 'message' => 'Error processing order item.']
                    );
                }
            }

            // Trigger check events for processed orders
            $this->triggerCheckEventsForProcessedOrders();

            $this->logger->log(
                'Processed ' . $processedCount . ' items. Triggered check events for ' . count($this->processedOrderIds) . ' orders.',
                self::CLASS_KEY
            );
        } catch (\Exception $e) {
            $this->logger->logException($e, self::CLASS_KEY, ['message' => 'Cron execute failed.']);
            throw $e;
        }

        $this->logger->log('------End Of Cron-CheckVirtualOrderTicketStatus-----', self::CLASS_KEY);
    }

    /**
     * Get target order item collection (virtual items).
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Item\Collection
     */
    protected function getTargetOrderItemCollection()
    {
        $collection = $this->orderItemCollectionFactory->create();

        $collection->addFieldToSelect([
            'item_id',
            'order_id',
            'product_id',
            'product_options',
            'flow_status',
            'is_virtual'
        ]);

        // Filter for virtual items only using sales_order_item.is_virtual
        $collection->addFieldToFilter('main_table.is_virtual', 1);

        // Only check items with flow_status = 'arrived'
        $collection->addFieldToFilter('main_table.flow_status', OrderStatus::STATUS_ARRIVED);

        // Only include items that already exist in customer_ticket table
        $collection->getSelect()->join(
            ['ct' => $collection->getTable('customer_ticket')],
            'ct.sales_order_item_id = main_table.item_id',
            []
        );

        // Exclude tickets whose status is 2 (unused)
        $collection->addFieldToFilter('ct.status', ['neq' => TicketStatus::STATUS_UNUSED]);
        
        // Group by item_id to avoid duplicates when one item has multiple tickets
        $collection->getSelect()->group('main_table.item_id');
        
        // Join with sales_order to exclude already completed orders
        $collection->getSelect()->join(
            ['so' => $collection->getTable('sales_order')],
            'main_table.order_id = so.entity_id',
            ['order_status' => 'so.status', 'order_state' => 'so.state']
        );

        // Exclude already completed orders
        $collection->addFieldToFilter('so.status', [
            'nin' => ['complete', 'canceled', 'closed']
        ]);

        // HAVING condition 1: 至少有一票券 status = 3 (STATUS_USED)
        // or all tickets are completed (STATUS_USED/STATUS_OVER_DUE or STATUS_UNUSED but use_end_time is overdue)
        $collection->getSelect()->having(
            'SUM(CASE WHEN ct.status IN (?, ?) THEN 1 
                      WHEN ct.status = ? AND ct.use_end_time < NOW() THEN 1 
                      ELSE 0 END) = COUNT(DISTINCT ct.record_id)',
            TicketStatus::STATUS_USED,
            TicketStatus::STATUS_OVER_DUE,
            TicketStatus::STATUS_UNUSED
        );

        // Limit to avoid processing too many at once
        $collection->getSelect()->limit(self::HANDLE_ORDER_LIMIT_PER_CRON);

        return $collection;
    }

    /**
     * Process a single order item.
     *
     * @param OrderItem $item
     * @return void
     */
    protected function processOrderItem(OrderItem $item)
    {
        $orderId = $item->getOrderId();
        $itemId = $item->getId();

        // Skip if item already processed in this run (avoid duplicate processing)
        if (in_array($itemId, $this->processedItemIds)) {
            return;
        }

        // Mark item as processed
        $this->processedItemIds[] = $itemId;

        // Skip if order already processed in this run
        if (in_array($orderId, $this->processedOrderIds)) {
            return;
        }

        // Get all tickets for this order item
        $ticketCollection = $this->customerTicketRepository->getByOrderItemIdsArray([$itemId]);

        if ($ticketCollection->getSize() == 0) {
            // No tickets found for this item, skip
            return;
        }

        // Check if all tickets are in complete status (used or over due)
        if ($this->ticketOrderHelper->checkIfTicketCollectionAllMatchCompleteStatus($ticketCollection)) {
            // All tickets are complete, mark order for status check
            $this->processedOrderIds[] = $orderId;

            $this->logger->log(
                [
                    'order_id' => (int) $orderId,
                    'item_id' => (int) $itemId,
                    'message' => 'All tickets are complete, order marked for status check.',
                ],
                self::CLASS_KEY
            );
        }
    }

    /**
     * Trigger check events for all processed orders.
     *
     * @return void
     */
    protected function triggerCheckEventsForProcessedOrders()
    {
        $this->processedOrderIds = array_unique($this->processedOrderIds);

        foreach ($this->processedOrderIds as $orderId) {
            try {
                $order = $this->orderRepository->get($orderId);

                // Double check it's a ticket order
                if (!$this->ticketOrderHelper->isTicketOrder($order)) {
                    continue;
                }

                // Trigger the check event
                $this->eventManager->dispatch(
                    EventName::CHECK_TICKET_ORDER_ALL_COMPLETE_MANUALLY,
                    ['orderId' => $orderId]
                );

                $this->logger->log(
                    [
                        'order_id' => (int) $orderId,
                        'message' => 'Triggered check_ticket_order_all_complete_manually event.',
                    ],
                    self::CLASS_KEY
                );
            } catch (\Exception $e) {
                $this->logger->logException(
                    $e,
                    self::CLASS_KEY,
                    ['order_id' => (int) $orderId, 'message' => 'Error triggering event for order.']
                );
            }
        }
    }
}

