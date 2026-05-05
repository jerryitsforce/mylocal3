<?php
namespace Branch8\HotaiAuth\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\HotaiAuth\Model\CustomLogger;
use Magento\Sales\Model\Order;

class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var CustomLogger
     */
    protected CustomLogger $customLogger;

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @param CustomLogger $customLogger
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CustomLogger $customLogger,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->customLogger = $customLogger;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order->getCustomerId()) {
            return;
        }

        $customerId = $order->getCustomerId();
        $logId = $this->customLogger->getLatestLoginLogId($customerId);

        if ($logId) {
            $order->setData('custom_login_log_id', $logId);
        }
    }
}
