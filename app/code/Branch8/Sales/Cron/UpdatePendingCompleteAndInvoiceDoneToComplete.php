<?php

declare(strict_types=1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Framework\DB\TransactionFactory;
use Branch8\HotaiCore\Model\Order\State as OrderState;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;

class UpdatePendingCompleteAndInvoiceDoneToComplete
{
    const CLASS_NAME = 'UpdatePendingCompleteAndInvoiceDoneToComplete';

    const HANDLE_ORDER_LIMIT_PER_CRON = 100;

    const LOG_PATH = 'Sales/Cron/updatePendingCompleteAndInvoiceDoneToComplete';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommon;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var TransactionFactory */
    protected $transactionFactory;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        TransactionFactory $transactionFactory
    ) {
        $this->hotaiCoreCommon        = $hotaiCoreCommonHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository        = $orderRepository;
        $this->transactionFactory     = $transactionFactory;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->hotaiCoreCommon->writeLog(
            "------Start Of Cron-" . self::CLASS_NAME . "-----",
            self::LOG_PATH
        );

        $orderCollection    = $this->getTargetOrderCollection();
        $targetOrderIdArray = $orderCollection->getAllIds();

        $this->hotaiCoreCommon->writeLog(
            "Target Order Ids: " . implode(',', $targetOrderIdArray),
            self::LOG_PATH
        );

        foreach ($orderCollection as $orderWithLessData) {
            // If we just use $orderWithLessData to save, it will empty the hotai_child_order_number.
            // Not sure why, but it happens.
            $order = $this->orderRepository->get($orderWithLessData->getId());

            try {
                $order->setState(OrderState::STATE_COMPLETE);
                $order->setStatus(OrderStatus::STATUS_COMPLETE);
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($order);

                foreach ($order->getAllVisibleItems() as $item) {
                    $oldItemStatus = $item->getFlowStatus();
                    $newItemStatus = OrderStatus::STATUS_COMPLETE;
                    $item->setFlowStatus($newItemStatus);
                    $transaction->addObject($item);

                    $name    = $item->getName();
                    $comment = "Update order item flow_status by UpdatePendingCompleteAndInvoiceDoneToComplete from ({$oldItemStatus}) to ({$newItemStatus}) for item: $name";
                    $history = $order->addCommentToStatusHistory($comment);
                    $history->setItemId($item->getId());
                    $history->setItemStatus($newItemStatus);
                    $transaction->addObject($history);
                }

                $transaction->save();

                $this->hotaiCoreCommon->writeLog(
                    "Update success order ID: " . ($order->getId() ?? 'N/A'),
                    self::LOG_PATH
                );
            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    json_encode([
                        "Failed order ID"   => $order->getId() ?? 'N/A',
                        "Exception message" => $e->getMessage()
                    ]),
                    self::LOG_PATH
                );
            }
        }

        $this->hotaiCoreCommon->writeLog(
            "------End Of Cron-" . self::CLASS_NAME . "-----",
            self::LOG_PATH
        );
    }

    private function getTargetOrderCollection()
    {
        $collection = $this->orderCollectionFactory->create();

        $collection->addFieldToSelect(
            [
                'entity_id',
                'state',
                'status',
                'ecpay_invoice_tag',
                'is_paid'
            ]
        );

        $collection->addFieldToFilter(
            'status',
            OrderStatus::STATUS_PENDING_COMPLETE
        );
        $collection->addFieldToFilter(
            'ecpay_invoice_tag',
            "1"
        );

        $collection->getSelect()->limit(self::HANDLE_ORDER_LIMIT_PER_CRON);

        return $collection;
    }
}
