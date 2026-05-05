<?php

namespace Branch8\TicketOrderStatusChangeObserver\Observer;

use Magento\Framework\Event\ObserverInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketOrderStatusChangeObserver\Helper\Common as CommonHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\HotaiCore\Model\Order\Status;
use Magento\Framework\DB\TransactionFactory;

class CheckTicketOrderForRefund implements ObserverInterface
{
    public const LOG_FOLDER_NAME = 'TicketOrderStatusChangeObserver/CheckTicketOrderForRefund';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var TransactionFactory */
    protected $transactionFactory;

    protected $logTitleArray;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        CustomerTicketRepository $customerTicketRepository,
        TransactionFactory $transactionFactory
    ) {
        $this->hotaiCoreCommonHelper    = $hotaiCoreCommonHelper;
        $this->commonHelper             = $commonHelper;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->transactionFactory       = $transactionFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $creditmemo = $observer->getEvent()->getCreditmemo();

            if (!($creditmemo instanceof \Magento\Sales\Model\Order\Creditmemo)) {
                return $this;
            }

            $order = $creditmemo->getOrder();

            $this->logTitleArray = [
                "Memo id"  => $creditmemo->getId(),
                "Order id" => $order->getId(),
            ];

            $this->writeLog([
                "Message" => "Observer start."
            ]);

            if ($this->commonHelper->isCreditmemoNeedFinancialReview($creditmemo)) {
                $this->writeLog([
                    "Message" => "This creditmemo needs finalcial review, observer end."
                ]);

                return;
            }

            if (!$this->commonHelper->isTicketOrder($order)) {
                $this->writeLog([
                    "Message" => "Not a ticket order, observer ends."
                ]);

                return $this;
            }

            $isPaid = $order->getData("is_paid");
            if ($isPaid != 1) {
                $this->writeLog([
                    "Message" => "Order is not paid, money order's creditmemo created by cancel action probably, observer ends."
                ]);

                return $this;
            }
            // -----------------------------------------------------

            $itemIdsArray = $this->commonHelper->getNotReturnedItemIdsFromOrder($order);

            // 如果$itemIdsArray是空的, 代表都被退貨, 只對order進行狀態變更, return
            if (count($itemIdsArray) == 0) {
                $this->writeLog([
                    "Message" => "Ready to update for CheckTicketOrderForRefund, every item returned.",
                ]);

                $this->updateForAllCompleteOnlyForOrder($order);

                $this->writeLog([
                    "Message" => "Update complete for CheckTicketOrderForRefund, observer end."
                ]);

                return $this;
            }

            // 如果$itemIdsArray不是空的, 檢查item的票券狀態, 全部使用或過期就進行狀態變更
            $collection = $this->customerTicketRepository->getByOrderItemIdsArray($itemIdsArray);

            if (!$this->commonHelper->checkIfTicketCollectionAllMatchCompleteStatus($collection)) {
                $this->writeLog([
                    "Message" => "Status check fail for CheckTicketOrderForRefund, observer end."
                ]);

                return;
            }

            $this->writeLog([
                "Message"  => "Ready to update for CheckTicketOrderForRefund.",
                "Item ids" => $itemIdsArray
            ]);

            $this->updateForAllCompleteForOrderAndItem($order, $itemIdsArray);

            $this->writeLog([
                "Message" => "Update complete for CheckTicketOrderForRefund, observer end."
            ]);

            return $this;
        } catch (\Throwable $t) {
            $this->writeLog([
                "Title"   => "Exception.",
                "Message" => $t->getMessage()
            ]);
        }
    }

    protected function updateForAllCompleteOnlyForOrder(Order $order): void
    {
        $order->setState($this->commonHelper->getOrderStateForTicketAllUsed());
        $order->setStatus($this->commonHelper->getOrderStatusForTicketAllUsed());
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order);

        $transaction->save();
    }

    protected function updateForAllCompleteForOrderAndItem(Order $order, array $itemIdsArray): void
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
            $comment = __("Update Order Item by CheckTicketOrderForRefund Observer for all complete: $name - Status To %1", $newItemStatus);
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
