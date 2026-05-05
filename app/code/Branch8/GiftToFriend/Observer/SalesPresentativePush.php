<?php
namespace Branch8\GiftToFriend\Observer;

use Magento\Framework\Event\Observer;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;

class SalesPresentativePush implements \Magento\Framework\Event\ObserverInterface
{
    protected $publisher;

    protected $timezone;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->publisher = $publisher;
        $this->timezone = $timezone;
    }

    public function execute(Observer $observer)
    {
        /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail */
        $parentOrder = $observer->getData('data_object');
        $salesPresentativeInfor = $parentOrder->getData('sales_presentative_infor');
        $oldStatus = $parentOrder->getOrigData('status');
        $newStatus = $parentOrder->getData('status');
        $isGiftOrder = $parentOrder->getData('is_gift_order');
        
        $oldRmaStatus = $parentOrder->getOrigData('rma_status');
        $newRmaStatus = $parentOrder->getData('rma_status');

        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'push_sale_presentative_order_status')){
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/cancel_parent.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($parentOrder->getParentId().'::'.$oldStatus.'__'.$newStatus);
            $logger->info(print_r(debug_backtrace(2), true));
        }

        if(
            (
                $isGiftOrder && $salesPresentativeInfor && (
                    ($oldStatus != $newStatus && in_array($newStatus, \Branch8\GiftToFriend\Model\SalesPresentativeConsumer::PUSH_STATUSES))
                    || ($oldRmaStatus != $newRmaStatus && in_array($newRmaStatus, \Branch8\GiftToFriend\Model\SalesPresentativeConsumer::RMA_PUSH_STATUSES))
                )
            )
        ){
            if($oldStatus != $newStatus && in_array($newStatus, \Branch8\GiftToFriend\Model\SalesPresentativeConsumer::PUSH_STATUSES)){
                $changeType = 'order_status';

                $orderStatus = HotaiOrderStatus::FORMAL_FLOW;
                $orderStatus[] = HotaiOrderStatus::STATUS_CANCELED;

                $statusOrderCheck = $this->statusOrderCheck(
                    array_keys($orderStatus, $oldStatus)[0],
                    array_keys($orderStatus, $newStatus)[0],
                );

                if(!$statusOrderCheck) {
                    return;
                }
            }
            
            if($oldRmaStatus != $newRmaStatus && in_array($newRmaStatus, \Branch8\GiftToFriend\Model\SalesPresentativeConsumer::RMA_PUSH_STATUSES)){
                $changeType = 'rma_status';
            }
            $this->publisher->publish(
                    'gift.order.push.sales_presentative',
                    json_encode([
                        'parent_order_id' => $parentOrder->getParentId(), 
                        'order_status' => $parentOrder->getStatus(), 
                        'rma_status' => $parentOrder->getRmaStatus(),
                        'time' => $this->timezone->convertConfigTimeToUtc($this->timezone->date()),
                        'changeType' => $changeType
                    ])
                );
        }
    }
    
    /**
     * statusOrderCheck
     *
     * @param  int $oldStatusOrder
     * @param  int $newStatusOrder
     * @return bool
     */
    protected function statusOrderCheck($oldStatusOrder, $newStatusOrder)
    {
        return (int) $oldStatusOrder < (int) $newStatusOrder;
    }
}