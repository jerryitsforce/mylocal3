<?php

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\HotaiCore\Model\Order\Status;

class UpdateOrderItemStatusAfterSaveComment implements ObserverInterface
{

    protected $updateOrderStatus;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    public function __construct(
        UpdateOrderStatus $updateOrderStatus,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->updateOrderStatus = $updateOrderStatus;
        $this->orderRepository   = $orderRepository;
    }

    /**
     * @param  Observer  $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $passInOrder = $observer->getEvent()->getOrder();

        $order = $this->orderRepository->get((int) $passInOrder->getEntityId());

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($order->getAllItems() as $item) {

            if ($order->getStatus() == Status::STATUS_TALLYING) {
                $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE);
            }

            $item->setFlowStatus($order->getStatus());
            $item->save();
            $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, $order->getStatus());
        }
    }
}
