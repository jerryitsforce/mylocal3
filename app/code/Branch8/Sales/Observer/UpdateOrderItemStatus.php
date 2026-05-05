<?php

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiCore\Helper\Status as HotaiCoreStatusHelper;

class UpdateOrderItemStatus implements ObserverInterface
{

    protected $updateOrderStatus;
    protected $hotaiCoreStatusHelper;

    public function __construct(
        UpdateOrderStatus $updateOrderStatus,
        HotaiCoreStatusHelper $hotaiCoreStatusHelper
    ) {
        $this->updateOrderStatus = $updateOrderStatus;
        $this->hotaiCoreStatusHelper = $hotaiCoreStatusHelper;
    }
    /**
     * @param  Observer  $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $paymentCode = $order->getPayment()->getMethod();
        if ($paymentCode == \Branch8\HotaiPay\Model\Payment\HotaiPay::CODE) {
            $status = Status::STATUS_PENDING_PAYMENT;
            $order->setStatus($status);
            $order->save();
        }

        if($order->getStatus() ==  Status::STATUS_PENDING) {
            return;
        }

        foreach ($order->getAllVisibleItems() as $item) {
            if ($this->hotaiCoreStatusHelper->checkFlowStatusSequenceForChange($item->getFlowStatus(), $order->getStatus(), $order, true, __CLASS__)){
                $item->setFlowStatus($order->getStatus());
            }
            $item->save();
            $this->updateOrderStatus->addItemStatusRecord($order->getId(), $item, $order->getStatus());
        }
    }
}
