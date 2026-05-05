<?php

namespace Branch8\CustomNotification\Observer;

use Branch8\AppNotification\Model\SendCustomerNotificationToHotaiApp;
use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;
use Magenest\NotificationBox\Model\CustomerToken as CustomerTokenModel;
use Magenest\NotificationBox\Model\CustomerTokenFactory;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\NotificationQueueFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory as CustomerNotificationCollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken;
use Magenest\NotificationBox\Model\ResourceModel\CustomerToken\CollectionFactory as Collection;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue;
use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Registry;

class SendNotification extends \Magenest\NotificationBox\Observer\SendNotification
{

    /**
     * @var Registry
     */
    protected $registry;
    private SendCustomerNotificationToHotaiApp $sendCustomerNotificationToHotaiApp;

    public function __construct(
        CustomerNotification $customerNotificationResource,
        Collection $tokenCollection,
        CustomerTokenFactory $customerTokenFactory,
        CustomerToken $customerTokenResource,
        CollectionFactory $collection,
        Json $serialize,
        CustomerNotificationCollectionFactory $customerNotification,
        CustomerNotificationFactory $customerNotificationFactory,
        Helper $helper, LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        NotificationQueue $notificationQueue,
        NotificationQueueFactory $notificationQueueFactory,
        Registry $registry,
        Session $checkoutSession,
        SendCustomerNotificationToHotaiApp $sendCustomerNotificationToHotaiApp
    )
    {
        $this->registry = $registry;
        $this->sendCustomerNotificationToHotaiApp = $sendCustomerNotificationToHotaiApp;
        parent::__construct($customerNotificationResource, $tokenCollection, $customerTokenFactory, $customerTokenResource, $collection, $serialize, $customerNotification, $customerNotificationFactory, $helper, $logger, $storeManager, $notificationQueue, $notificationQueueFactory, $checkoutSession);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->registry->registry('is_cron_status_update')) {
            return;
        }

        if(!$this->helper->getEnableModule()){
            return;
        }
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        try {
            $order = $observer->getOrder();
            $customerId = $order->getCustomerId();
            $orderId = $order->getId();
            $orderStatus = $order->getStatus();
            $beforeStatus = $order->getOrigData('status');
            $storeId = $order->getStoreId();
            $customerGroupId = $order->getCustomerGroupId();

            // Check if notification was already sent for this status change
            $registryKey = 'notification_sent_' . $orderId . '_' . $orderStatus;
            if ($this->registry->registry($registryKey)) {
                return;
            }

            // Register that we're sending notification for this status
            $this->registry->register($registryKey, true);

            $listNotification = $this->collection
                ->addFieldToFilter('is_active', Notification::ACTIVE)
                ->addFieldToFilter('notification_type', [Notification::ORDER_STATUS_UPDATE,Notification::REVIEW_REMINDERS])
                ->getData();
            $customerToken = $this->tokenCollection->create()
                ->addFieldToFilter('store_id',$storeId)
                ->addFieldToFilter('is_active', CustomerTokenModel::IS_ACTIVE)
                ->addFieldToFilter('status', CustomerTokenModel::STATUS_SUBSCRIBED)
                ->addFieldToFilter('customer_id',$customerId);
            foreach ($listNotification as $key => $notification) {
                $listOrderStatus = $this->serialize->unserialize($notification['condition']);
                if (!in_array($orderStatus, $listOrderStatus) || $orderStatus == $beforeStatus) {
                    unset($listNotification[$key]);
                    continue;
                }
                //remove notification if not meet conditions
                $listStore = $this->serialize->unserialize($notification['store_view']);
                if (is_array($listStore) && !in_array('0', $listStore) && !in_array($storeId, $listStore)) {
                    unset($listNotification[$key]);
                    continue;
                }

                $listCustomerGroup = $this->serialize->unserialize($notification['customer_group']);
                //remove notification if not meet conditions
                if (is_array($listCustomerGroup) && !in_array('0', $listCustomerGroup) && !in_array($customerGroupId, $listCustomerGroup)) {
                    unset($listNotification[$key]);
                    continue;
                }

                //add notification to queue
                if($notification['send_time'] == 'schedule_time' || $notification['send_time'] == 'send_after_the_trigger_condition'){
                    unset($notification['created_at']);
                    unset($notification['update_at']);
                    $notification['customer_id'] = $customerId;
                    $notification['order_id'] = $order->getId();
                    $notification['description'] = str_replace('{{order_id}}', '#'.$order->getId(), $notification['description']);
                    $notification['description'] = str_replace('{{order_status}}', $order->getStatus(), $notification['description']);
                    $notification['notification_id'] = $notification['id'] ?? 0;
                    $notificationQueueModel = $this->notificationQueueFactory->create();
                    $notificationQueueModel->addData($notification);
                    $this->notificationQueue->save($notificationQueueModel);
                    continue;
                }

                if($notification['send_time'] == 'send_immediately'){
                    unset($notification['created_at']);
                    $notification['customer_id'] = $customerId;
                    $notification['order_id'] = $order->getId();
                    $notification['icon'] = $notification['image'];
                    $notification['star'] = CustomerNotificationModel::UNSTAR;
                    $notification['status'] = CustomerNotificationModel::STATUS_UNREAD;
                    $notification['notification_id'] = $notification['id'] ?? 0;
                    $customerNotification = $this->customerNotificationFactory->create();
                    $customerNotification->addData($notification);
                    $this->customerNotificationResource->save($customerNotification);
                    $notification['customer_notification_id'] = $customerNotification->getId();
                    $this->sendCustomerNotificationToHotaiApp->execute($notification, true);

                    $tokenSent = [];
                    foreach ($customerToken as $token) {
                        $currentToken =  ['token' => $token->getToken(), 'id'=>$token->getGuestId()];
                        if(!in_array($currentToken,$tokenSent)){
                            $this->helper->sendNotificationWithFireBase($notification,$token);
                        }
                        $tokenSent[] = $currentToken;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
