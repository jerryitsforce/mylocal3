<?php

namespace Branch8\CustomNotification\Observer;

use Branch8\Rma\Helper\RmaActions;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\CustomerToken as CustomerTokenModel;
use Magenest\NotificationBox\Model\Notification;
class SendNotificationReturn implements ObserverInterface
{

    protected $helper;
    /** @var CollectionFactory */
    protected $collection;
    protected $tokenCollection;
    protected $serialize;
    protected $customerNotificationFactory;
    protected $customerNotificationResource;
    protected $logger;
    protected $rmaActions;
    protected $orderFactory;
    /** @var NotificationQueueFactory  */
    protected $notificationQueueFactory;

    /** @var NotificationQueue  */
    protected $notificationQueue;

    public function __construct(
        \Magenest\NotificationBox\Helper\Helper $helper,
        \Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory $collection,
        \Magento\Framework\Serialize\SerializerInterface $serialize,
        \Magenest\NotificationBox\Model\CustomerNotificationFactory $customerNotificationFactory,
        \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification $customerNotificationResource,
        \Psr\Log\LoggerInterface $logger,
        RmaActions                                           $rmaActions,
        \Magento\Sales\Model\OrderFactory                    $orderFactory,
        NotificationQueue $notificationQueue,
        NotificationQueueFactory $notificationQueueFactory
    ) {
        $this->helper = $helper;
        $this->collection = $collection->create();
        $this->serialize = $serialize;
        $this->rmaActions = $rmaActions;
        $this->customerNotificationFactory = $customerNotificationFactory;
        $this->customerNotificationResource = $customerNotificationResource;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->notificationQueue = $notificationQueue;
        $this->notificationQueueFactory = $notificationQueueFactory;
    }

    /**
     * Handle return_exchange_change event
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->getEnableModule()) {
            return;
        }

        try {
            $rmaId = $observer->getData('rma_id');
            $items = $this->rmaActions->getRmaItemCollection($rmaId);
            if (count($items) >= 1) {
                $firstItem = reset($items);

                // Get the order ID from the first RMA item
                $orderId = $firstItem->getOrderId();
                $order = $this->orderFactory->create()->load($orderId);
                $customerId = $order->getCustomerId();
                $storeId = $order->getStoreId();
                $customerGroupId = $order->getCustomerGroupId();
                // Get active notifications for specific order status
                $listNotification = $this->collection
                    ->addFieldToFilter('is_active', Notification::ACTIVE)
                    ->addFieldToFilter('notification_type', ['return_exchange'])
                    ->getData();
                foreach ($listNotification as $key => $notification) {

                    // Remove notifications that don't match store conditions
                    $listStore = $this->serialize->unserialize($notification['store_view']);
                    if (is_array($listStore) && !in_array('0', $listStore) && !in_array($storeId, $listStore)) {
                        unset($listNotification[$key]);
                        continue;
                    }

                    // Remove notifications that don't match customer group conditions
                    $listCustomerGroup = $this->serialize->unserialize($notification['customer_group']);
                    if (is_array($listCustomerGroup) && !in_array('0', $listCustomerGroup) && !in_array($customerGroupId, $listCustomerGroup)) {
                        unset($listNotification[$key]);
                        continue;
                    }

                    // Add notification to queue or send immediately
                    if ($notification['send_time'] == 'schedule_time' || $notification['send_time'] == 'send_after_the_trigger_condition') {
                        unset($notification['created_at']);
                        unset($notification['update_at']);
                        $notification['customer_id'] = $customerId;
                        $notification['order_id'] = $order->getId();
                        $notification['description'] = str_replace('{{order_id}}', '#' . $order->getId(), $notification['description']);
                        $notification['description'] = str_replace('{{order_status}}', $order->getStatus(), $notification['description']);
                        $notificationQueueModel = $this->notificationQueueFactory->create();
                        $notificationQueueModel->addData($notification);
                        $this->notificationQueue->save($notificationQueueModel);
                        continue;
                    }

                    if ($notification['send_time'] == 'send_immediately') {
                        unset($notification['created_at']);
                        $notification['customer_id'] = $customerId;
                        $notification['order_id'] = $order->getId();
                        $notification['icon'] = $notification['image'];
                        $notification['star'] = CustomerNotificationModel::UNSTAR;
                        $notification['status'] = CustomerNotificationModel::STATUS_UNREAD;
                        $customerNotification = $this->customerNotificationFactory->create();
                        $customerNotification->addData($notification);
                        $this->customerNotificationResource->save($customerNotification);
                    }
                }
            }
            $this->logger->info("Processing notification for return_exchange_change event, RMA ID: " . $rmaId);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
