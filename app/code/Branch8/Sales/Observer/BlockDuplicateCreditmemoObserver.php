<?php
namespace Branch8\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Sales\Model\Order\Item as OrderItem;

class BlockDuplicateCreditmemoObserver implements ObserverInterface
{
    /**
     * @var CreditmemoCollectionFactory
     */
    protected $creditmemoCollectionFactory;

    /**
     * @param CreditmemoCollectionFactory $creditmemoCollectionFactory
     */
    public function __construct(
        CreditmemoCollectionFactory $creditmemoCollectionFactory
    ) {
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
    }

    /**
     * Validate credit memo quantities before saving
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        if($creditmemo->getId()){
            return;
        }

        if($creditmemo->getData('stop_validation')) {
            return; // Skip validation if explicitly set
        }

        $order = $creditmemo->getOrder();

        // Check existing credit memos for the order
        $existingCreditmemos = $this->creditmemoCollectionFactory->create()
            ->addFieldToFilter('order_id', $order->getId());

        // Get order items and their refunded quantities
        $orderItems = $order->getAllItems();
        $refundedQuantities = $this->getRefundedQuantities($existingCreditmemos);

        // Validate credit memo items' quantities
        if(count($creditmemo->getAllItems()) === 0) {
            throw new LocalizedException(__('Credit memo must have at least one item.'));
        }
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            
            if (!$creditmemoItem->getBackToStock()) {
                continue; // Skip items not marked for return to stock
            }

            $orderItemId = $creditmemoItem->getOrderItemId();
            $refundQty = $creditmemoItem->getQty();

            // Find the corresponding order item
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getId() == $orderItemId) {
                    // Skip non-refunded items (e.g., parent items of configurable products)
                    if ($orderItem->isDummy()) {
                        continue;
                    }

                    $orderedQty = $orderItem->getQtyOrdered();
                    $alreadyRefundedQty = isset($refundedQuantities[$orderItemId]) ? $refundedQuantities[$orderItemId] : 0;

                    // Calculate remaining quantity available to refund
                    $remainingQty = $orderedQty - $alreadyRefundedQty;

                    // Validate refund quantity
                    if ($refundQty > $remainingQty) {
                        throw new LocalizedException(
                            __(
                                "Cannot refund %1 units of '%2'. Only %3 units remain to be refunded.",
                                $refundQty,
                                $creditmemoItem->getName(),
                                $remainingQty
                            )
                        );
                    }
                    break;
                }
            }
        }
    }

    /**
     * Get total refunded quantities per order item from existing credit memos
     *
     * @param CreditmemoCollectionFactory $creditmemos
     * @return array
     */
    protected function getRefundedQuantities($creditmemos)
    {
        $refundedQuantities = [];
        foreach ($creditmemos as $creditmemo) {
            foreach ($creditmemo->getAllItems() as $item) {
                $orderItemId = $item->getOrderItemId();
                $refundedQuantities[$orderItemId] = ($refundedQuantities[$orderItemId] ?? 0) + $item->getQty();
            }
        }
        return $refundedQuantities;
    }
}
