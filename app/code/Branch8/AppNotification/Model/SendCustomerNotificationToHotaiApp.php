<?php
namespace Branch8\AppNotification\Model;

use Branch8\AppNotification\Api\Data\NotificationMessageInterfaceFactory;
use Branch8\AppNotification\Model\Queue\SendAppNotification;
use Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken\CollectionFactory;
use Magenest\NotificationBox\Helper\Helper;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class SendCustomerNotificationToHotaiApp
{

    public function __construct(
        protected NotificationMessageInterfaceFactory $notificationMessageInterfaceFactory,
        protected CollectionFactory $customerFcmTokenCollectionFactory,
        protected Helper $helper,
        protected  PublisherInterface $publisher,
        protected Json $json,
        protected LoggerInterface $logger
    ) {
    }

    public function execute(array $notification, $personal = true)
    {
        $this->logger->info('Send Customer Notification to Hotai App', [
            'notification' => $notification,
            'personal' => $personal
        ]);
        $customerId = $notification['customer_id'] ?? null;
        if (!$customerId) {
            return;
        }

        $customerTokens = $this->getCustomerTokens($customerId);
        $tokenCustomerIds = [];
        foreach ($customerTokens as $token) {
            $tokenCustomerIds[$token] = $customerId;
        }

        $urlImage = $this->helper->getImageByNotificationType($notification);

        $messageData = $this->notificationMessageInterfaceFactory->create()
            ->setTokens(implode(',', $customerTokens))
            ->setTitle($notification['name'])
            ->setBody($notification['description'])
            ->setData($this->json->serialize([
                "notificationId" => $notification['id'],
                "title" => $notification['name'],
                "body" => $notification['description'],
                "icon" => $urlImage,
                "target" => "notification",
                "activeTab" => $personal ? 1 : 0, //TODO all: 0, personal: 1
                "orderId" => $notification['order_id'] ?? null,
                'customerNotificationId' => $notification['customer_notification_id'] ?? null,
            ]))
            ->setAdditionalData($this->json->serialize([
                "token_customer_ids" => $tokenCustomerIds
            ]));

        $this->publisher->publish(SendAppNotification::TOPIC_NAME, $messageData);
    }

    public function getCustomerTokens($customerId, $storeId = null)
    {
        $collection = $this->customerFcmTokenCollectionFactory->create();

        $collection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('is_subscribed', 1);

        if ($storeId) {
            $collection->addFieldToFilter('store_id', $storeId);
        }

        $tokens = [];
        foreach ($collection as $item) {
            $tokens[] = $item->getToken();
        }

        return $tokens;
    }
}
