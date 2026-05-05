<?php

namespace Branch8\TicketOrderStatusChangeObserver\Observer;

use Magento\Framework\Event\ObserverInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketOrderStatusChangeObserver\Helper\Common as CommonHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;

class CheckTicketOrderAfterFinancialReviewDone implements ObserverInterface
{
    public const LOG_FOLDER_NAME = 'TicketOrderStatusChangeObserver/CheckTicketOrderAfterFinancialReviewDone';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var CustomerTicketRepository */
    protected $customerTicketRepository;

    /** @var TransactionFactory */
    protected $transactionFactory;

    /** @var CreditmemoRepositoryInterface */
    protected $creditmemoRepository;

    protected $logTitleArray;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        CustomerTicketRepository $customerTicketRepository,
        TransactionFactory $transactionFactory,
        CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->hotaiCoreCommonHelper    = $hotaiCoreCommonHelper;
        $this->commonHelper             = $commonHelper;
        $this->customerTicketRepository = $customerTicketRepository;
        $this->transactionFactory       = $transactionFactory;
        $this->creditmemoRepository     = $creditmemoRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $memoId = $observer->getDataByKey("memoId");

        $this->logTitleArray = [
            "Memo id" => $memoId,
        ];

        $this->writeLog([
            "Message" => "Observer start."
        ]);

        try {
            $memoId     = $observer->getDataByKey("memoId");
            $creditmemo = $this->creditmemoRepository->get($memoId);
        } catch (NoSuchEntityException $e) {
            $this->writeLog([
                "Message" => "Can't find creditmemo, observer end."
            ]);

            return $this;
        }

        try {
            $order = $creditmemo->getOrder();
            $currentStatus = $order->getStatus();

            if (!$this->commonHelper->isTicketOrder($order)) {
                $this->writeLog([
                    "Message" => "Not a ticket order, observer ends."
                ]);

                return $this;
            }

            if (in_array($currentStatus, 
                    [OrderStatus::STATUS_CANCEL_PENDING, OrderStatus::STATUS_CANCELED])) {
                    $this->writeLog([
                        "Message" => "Order status is in cancel_pending or canceled status, skip pending_complete update.",
                    ]);

                return $this;
            }
            // -----------------------------------------------------

            $itemIdsArray = $this->commonHelper->getNotReturnedItemIdsFromOrder($order);

            // 如果$itemIdsArray是空的, 代表都被退貨, 只對order進行狀態變更, return
            if (count($itemIdsArray) == 0) {
                $this->writeLog([
                    "Message" => "Ready to update for CheckTicketOrderAfterFinancialReviewDone, every item returned.",
                ]);

                $this->updateForAllCompleteOnlyForOrder($order);

                $this->writeLog([
                    "Message" => "Update complete for CheckTicketOrderAfterFinancialReviewDone, observer end."
                ]);

                return $this;
            }

            // 如果$itemIdsArray不是空的, 檢查item的票券狀態, 全部使用或過期就進行狀態變更
            $collection = $this->customerTicketRepository->getByOrderItemIdsArray($itemIdsArray);

            if (!$this->commonHelper->checkIfTicketCollectionAllMatchCompleteStatus($collection)) {
                $this->writeLog([
                    "Message" => "Status check fail for CheckTicketOrderAfterFinancialReviewDone, observer end."
                ]);

                return;
            }

            $this->writeLog([
                "Message"  => "Ready to update for CheckTicketOrderAfterFinancialReviewDone.",
                "Item ids" => $itemIdsArray
            ]);

            $this->updateForAllCompleteForOrderAndItem($order, $itemIdsArray);

            $this->writeLog([
                "Message" => "Update complete for CheckTicketOrderAfterFinancialReviewDone, observer end."
            ]);

            return $this;
        } catch (\Throwable $t) {
            $this->writeLog([
                "Title"   => "Exception.",
                "Message" => $t->getMessage()
            ]);

            return $this;
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
            $comment = __("Update Order Item by CheckTicketOrderAfterFinancialReviewDone Observer for all complete: $name - Status To %1", $newItemStatus);
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
