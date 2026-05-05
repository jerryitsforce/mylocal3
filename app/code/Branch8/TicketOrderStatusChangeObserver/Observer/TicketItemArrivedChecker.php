<?php

namespace Branch8\TicketOrderStatusChangeObserver\Observer;

use Magento\Framework\Event\ObserverInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketOrderStatusChangeObserver\Helper\Common as CommonHelper;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Order\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\Item as OrderItemResource;
use Magento\Sales\Model\Order\Status\HistoryFactory as OrderStatusHistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\History as OrderStatusHistoryResource;

class TicketItemArrivedChecker implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'TicketOrderStatusChangeObserver/TicketItemArrivedChecker';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var OrderItemResource */
    protected $orderItemResource;

    /** @var OrderStatusHistoryFactory */
    protected $orderStatusHistoryFactory;

    /** @var OrderStatusHistoryResource */
    protected $orderStatusHistoryResource;

    protected $logTitleArray;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderRepository $orderRepository,
        CommonHelper $commonHelper,
        CustomerTicketRepository $customerTicketRepository,
        ResourceConnection $resourceConnection,
        OrderItemResource $orderItemResource,
        OrderStatusHistoryFactory $orderStatusHistoryFactory,
        OrderStatusHistoryResource $orderStatusHistoryResource
    ) {
        $this->hotaiCoreCommonHelper    = $hotaiCoreCommonHelper;
        $this->orderRepository          = $orderRepository;
        $this->commonHelper             = $commonHelper;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->resourceConnection       = $resourceConnection;
        $this->orderItemResource        = $orderItemResource;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->orderStatusHistoryResource = $orderStatusHistoryResource;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $orderId = $observer->getDataByKey("orderId");

            $this->logTitleArray = [
                "Order id" => $orderId,
            ];

            $this->writeLog([
                "Message" => "Observer start."
            ]);

            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);

            $this->updateItemStatus($order);
        } catch (\Throwable $t) {
            $this->writeLog([
                "Title"   => "Exception.",
                "Message" => $t->getMessage()
            ]);
        }

        $this->writeLog([
            "Message" => "Observer end."
        ]);
    }

    protected function isTicketRecordCountMatchQty(OrderItem $item): bool
    {
        $qty          = (int) $item->getQtyOrdered();
        $collection   = $this->customerTicketRepository->getByOrderItemIdsArray([$item->getId()]);
        $recordsCount = (int) $collection->getSize();

        $check = $qty == $recordsCount;

        $this->writeLog([
            "Message" => "Qty: {$qty}, recordsCount: {$recordsCount}.",
        ]);

        if (!$check) {
            $this->writeLog([
                "Title"                            => "Ticket records in customer_ticket not match item qty.",
                "Item ID"                          => $item->getId(),
                "Order Item qty_ordered"           => $qty,
                "Records count in customer_ticket" => $recordsCount
            ]);
        }

        $this->writeLog([
            "Message" => "Pass qty checker.",
        ]);

        return $check;
    }

    protected function updateItemStatus(Order $order): void
    {
        $this->writeLog([
            "Message" => "updateItemStatus check start."
        ]);

        $connection = $this->resourceConnection->getConnection();

        /** @var OrderItem $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $this->writeLog([
                "Message" => "getAllVisibleItems loop start, item ID: {$item->getId()}."
            ]);

            try {
                if (!$this->commonHelper->isTicketItem($item)) {
                    $this->writeLog([
                        "Message" => "Not a ticket item.",
                    ]);
                    continue;
                }

                $this->writeLog([
                    "Message" => "Pass isTicketItem check.",
                ]);

                if (!$this->isTicketRecordCountMatchQty($item)) {
                    continue;
                }

                $this->writeLog([
                    "Message" => "Pass isTicketRecordCountMatchQty check.",
                ]);

                if ($item->getFlowStatus() == Status::STATUS_GIFT_INFO_PENDING) {
                    $this->writeLog([
                        "Message" => "Flow Status is Gift Info Pending. Skip.",
                    ]);
                    continue;
                }

                $newItemStatus = Status::STATUS_ARRIVED;
                $item->setFlowStatus($newItemStatus);

                $connection->beginTransaction();
                try {
                    // 直接以 connection->update 寫入 flow_status，避免整個 model save 的額外開銷
                    $connection->update(
                        $this->orderItemResource->getMainTable(),
                        ['flow_status' => $newItemStatus],
                        ['item_id = ?' => $item->getId()]
                    );

                    $name    = $item->getName();
                    $comment = __("Ticket Item Arrived Checker Update Order Item: $name - Status To %1", $newItemStatus);
                    $history = $this->orderStatusHistoryFactory->create();
                    $history->setParentId($order->getId());
                    $history->setStatus($order->getStatus());
                    $history->setComment($comment);
                    $history->setEntityName('order');
                    $history->setIsCustomerNotified(false);
                    $history->setIsVisibleOnFront(false);
                    $history->setItemId($item->getId());
                    $history->setItemStatus($newItemStatus);
                    $this->orderStatusHistoryResource->save($history);

                    $connection->commit();
                } catch (\Throwable $t) {
                    $connection->rollBack();
                    throw $t;
                }

                $this->writeLog([
                    "Message" => "Transaction save end, newItemStatus: {$newItemStatus}.",
                ]);
            } catch (\Throwable $t) {
                $this->writeLog([
                    "Title"   => "Exception in updateItemStatus.",
                    "Item ID" => $item->getId(),
                    "Message" => $t->getMessage()
                ]);
            }

            $this->writeLog([
                "Message" => "getAllVisibleItems loop end, item ID: {$item->getId()}."
            ]);
        }

        $this->writeLog([
            "Message" => "updateItemStatus check end."
        ]);
    }

    protected function writeLog(array $messageArray): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Log title" => $this->logTitleArray,
            "Log data"  => $messageArray
        ]), self::LOG_FOLDER_NAME);
    }
}
