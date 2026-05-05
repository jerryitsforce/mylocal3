<?php

namespace Branch8\TicketOrderStatusChangeObserver\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketOrderStatusChangeObserver\Helper\Common as CommonHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Magento\Framework\DB\TransactionFactory;

class CheckTicketOrder implements ObserverInterface
{
    public const LOG_FOLDER_NAME = 'TicketOrderStatusChangeObserver/CheckTicketOrder';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var TransactionFactory */
    protected $transactionFactory;

    protected $logTitleArray;
    protected $eventName;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        OrderRepository $orderRepository,
        CustomerTicketRepository $customerTicketRepository,
        TransactionFactory $transactionFactory
    ) {
        $this->hotaiCoreCommonHelper    = $hotaiCoreCommonHelper;
        $this->commonHelper             = $commonHelper;
        $this->orderRepository          = $orderRepository;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->transactionFactory       = $transactionFactory;
    }

    public function execute(Observer $observer)
    {
        try {
            $this->eventName = $observer->getEvent()->getName();
            $orderId         = $observer->getDataByKey("orderId");

            $this->logTitleArray = [
                "Triggered event" => $this->eventName,
                "Order id"        => $orderId,
            ];

            $this->writeLog([
                "Message" => "Observer start."
            ]);

            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);

            if (!$this->commonHelper->isTicketOrder($order)) {
                $this->writeLog([
                    "Message" => "Not a ticket order, observer ends."
                ]);

                return;
            }

            // -----------------------------------------------------------------

            switch ($this->eventName) {
                case EventName::CHECK_TICKET_ORDER_FOR_USE_API_HANDLE:
                    $this->handleForAllMatchCompleteStatus($order);
                    break;

                case EventName::CHECK_TICKET_ORDER_FOR_CANCEL_API_HANDLE:
                    $this->handleForNotAllMatchCompleteStatus($order);
                    break;

                case EventName::CHECK_TICKET_ORDER_FOR_OVER_DUE_CRON:
                    $this->handleForAllMatchCompleteStatus($order);
                    break;

                case EventName::CHECK_TICKET_ORDER_ALL_COMPLETE_MANUALLY:
                    $this->handleForAllMatchCompleteStatus($order);
                    break;

                case EventName::CHECK_TICKET_ORDER_NOT_ALL_COMPLETE_MANUALLY:
                    $this->handleForNotAllMatchCompleteStatus($order);
                    break;

                default:
                    throw new \Exception("Not a valid event name.");
            }
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

    protected function handleForAllMatchCompleteStatus(Order $order)
    {
        $itemIdsArray = $this->commonHelper->getNotReturnedItemIdsFromOrder($order);
        if (count($itemIdsArray) == 0) {
            $this->writeLog([
                "Message" => "Item array is empty for handleForAllMatchCompleteStatus, return."
            ]);
            return;
        }

        $collection = $this->customerTicketRepository->getByOrderItemIdsArray($itemIdsArray);

        if (!$this->commonHelper->checkIfTicketCollectionAllMatchCompleteStatus($collection)) {
            $this->writeLog([
                "Message" => "Status check fail for handleForAllMatchCompleteStatus, return."
            ]);
            return;
        }

        $this->writeLog([
            "Message"  => "Ready to update for handleForAllMatchCompleteStatus.",
            "Item ids" => $itemIdsArray
        ]);

        $this->updateForAllComplete($order, $itemIdsArray);

        $this->writeLog([
            "Message" => "Update complete for handleForAllMatchCompleteStatus."
        ]);
    }

    protected function handleForNotAllMatchCompleteStatus(Order $order)
    {
        $itemIdsArray = $this->commonHelper->getNotReturnedItemIdsFromOrder($order);
        if (count($itemIdsArray) == 0) {
            $this->writeLog([
                "Message" => "Item array is empty for handleForNotAllMatchCompleteStatus, return."
            ]);
            return;
        }

        $collection = $this->customerTicketRepository->getByOrderItemIdsArray($itemIdsArray);

        if ($this->commonHelper->checkIfTicketCollectionAllMatchCompleteStatus($collection)) {
            $this->writeLog([
                "Message" => "Status check fail for handleForNotAllMatchCompleteStatus, return."
            ]);
            return;
        }

        $this->writeLog([
            "Message"  => "Ready to update for handleForNotAllMatchCompleteStatus.",
            "Item ids" => $itemIdsArray
        ]);

        $this->updateForNotAllComplete($order, $itemIdsArray);

        $this->writeLog([
            "Message" => "Update complete for handleForNotAllMatchCompleteStatus."
        ]);
    }

    protected function updateForAllComplete(Order $order, array $itemIdsArray): void
    {
        $order->setState($this->commonHelper->getOrderStateForTicketAllUsed());
        $order->setStatus($this->commonHelper->getOrderStatusForTicketAllUsed());
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order);

        /** @var OrderItem $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if (!in_array($item->getId(), $itemIdsArray)) {
                continue;
            }

            $newItemStatus = $this->commonHelper->getItemFlowStatusForTicketAllUsed();
            $item->setFlowStatus($newItemStatus);
            $transaction->addObject($item);

            $name    = $item->getName();
            $comment = __("Update Order Item by CheckTicketOrder Observer({$this->eventName}) for all complete: $name - Status To %1", $newItemStatus);
            $history = $order->addCommentToStatusHistory($comment);
            $history->setItemId($item->getId());
            $history->setItemStatus($newItemStatus);
            $transaction->addObject($history);
        }

        $transaction->save();
    }

    protected function updateForNotAllComplete(Order $order, array $itemIdsArray): void
    {
        $order->setState(State::STATE_PROCESSING);
        $order->setStatus(Status::STATUS_ARRIVED);
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order);

        /** @var OrderItem $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if (!in_array($item->getId(), $itemIdsArray)) {
                continue;
            }

            $newItemStatus = Status::STATUS_ARRIVED;
            $item->setFlowStatus($newItemStatus);
            $transaction->addObject($item);

            $name    = $item->getName();
            $comment = __("Update Order Item by CheckTicketOrder Observer({$this->eventName}) for not all complete: $name - Status To %1", $newItemStatus);
            $history = $order->addCommentToStatusHistory($comment);
            $history->setItemId($item->getId());
            $history->setItemStatus($newItemStatus);
            $transaction->addObject($history);
        }

        $transaction->save();
    }

    protected function writeLog(array $messageArray): void
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Log title" => $this->logTitleArray,
            "Log data"  => $messageArray
        ]), self::LOG_FOLDER_NAME);
    }
}