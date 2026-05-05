<?php

namespace Branch8\AppNotification\Model\Queue;

use Branch8\AppNotification\Api\Data\NotificationMessageInterface;
use Branch8\AppNotification\Api\NotificationServiceInterface;
use Branch8\AppNotification\Model\CustomerNotificationManagement;

class SendAppNotification
{
    public const TOPIC_NAME = 'send.app.notification';

    public function __construct(
        protected NotificationServiceInterface $notificationService,
        protected CustomerNotificationManagement $customerNotificationManagement,
        protected \Psr\Log\LoggerInterface $logger
    ) {
    }

    public function execute(NotificationMessageInterface $notificationMessage)
    {
        $this->logger->info('Send App Notification');
        $tokens = $notificationMessage->getTokens();
        $title = $notificationMessage->getTitle();
        $body = $notificationMessage->getBody();
        $data = $notificationMessage->getData();
        $additionalData = $notificationMessage->getAdditionalData();

        $badgeByToken = [];
        if ($additionalData) {
            $additionalData = json_decode($additionalData, true);
            if (isset($additionalData['token_customer_ids']) && is_array($additionalData['token_customer_ids'])) {
                $tokensCustomerIds = $additionalData['token_customer_ids'];
                $customerIds = array_values($tokensCustomerIds);
                $unreadCounts = $this->customerNotificationManagement->getUnreadCountByCustomerIds($customerIds);
                foreach ($tokensCustomerIds as $token => $customerId) {
                    $badgeByToken[$token] = isset($unreadCounts[$customerId]) ? (int)$unreadCounts[$customerId] : 0;
                }
            }
        }


        $tokens = $tokens ? explode(',', $tokens) : [];

        if (empty($tokens)) {
            return;
        }

        $data = $data ? json_decode($data, true) : [];


        if (count($tokens) === 1) {
            $this->notificationService->send(
                $tokens[0],
                $title,
                $body,
                $data,
                $badgeByToken[$tokens[0]] ?? 0
            );
        } else {
            $this->notificationService->sendBatch(
                $tokens,
                $title,
                $body,
                $data,
                $badgeByToken
            );
        }
    }

}
