<?php

namespace Branch8\Marketplace\Model\Actions;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Manager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Branch8\Marketplace\Service\MarketplaceLogger;

class OrderFailedDeliveryHandle
{
    const FAILED_DELIVERY_STATUS = 'failed_delivery';
    private ResourceConnection $resourceConnection;

    private ParenOrderManagement $parenOrderManagement;
    private MarketplaceLogger $marketplaceLogger;
    private Manager $_eventManager;
    private OrderRepository $orderRepository;

    /**
     * @param ParenOrderManagement $parenOrderManagement
     * @param ResourceConnection $resourceConnection
     * @param Manager $eventManager
     * @param MarketplaceLogger $marketplaceLogger
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        ParenOrderManagement $parenOrderManagement,
        ResourceConnection   $resourceConnection,
        Manager              $eventManager,
        MarketplaceLogger    $marketplaceLogger,
        OrderRepository      $orderRepository
    )
    {
        $this->_eventManager = $eventManager;
        $this->resourceConnection = $resourceConnection;
        $this->parenOrderManagement = $parenOrderManagement;
        $this->marketplaceLogger = $marketplaceLogger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Order $order
     * @param string $reason
     * @return bool
     */
    public function execute(Order $order, string $reason = '')
    {
        try {
            /**
             * Edge case:
             * When order have item invoiced  , can not cancel order
             */

            if(!$order->getData('is_paid')) {
                $order->cancel();
                $order->registerCancellation();
                $this->orderRepository->save($order);
                $this->_eventManager->dispatch('order_cancel_after', ['order' => $order]);
            } else {
                $this->_eventManager->dispatch(
                    'sales_order_cancel_paied_order',
                    ['order' => $order]
                );
            }
            
            $comment = ' Reason: ' . $reason;
            $this->parenOrderManagement->setComment($comment);
            $this->parenOrderManagement->updateSubOrderStatus(
                $order, Status::STATUS_CANCELED
            );
            foreach ($order->getItems() as $item) {
                $item->setFlowStatus(Status::STATUS_FAILED_DELIVERY);
                $item->save();
            }
            return true;
        } catch (\Exception $exception) {
            $this->marketplaceLogger->logException('OrderFailedDeliveryHandle', $exception, [
                'order_id' => $order->getId(),
                'reason' => $reason,
            ]);
            return false;
        }
    }
}
