<?php
namespace Branch8\AppNotification\Api;

interface CustomerFcmTokenManagementInterface
{
    /**
     * Save FCM Token for customer or guest
     *
     * @param int $customerId
     * @param string $fcmToken
     * @return bool
     */
    public function saveToken($customerId, $fcmToken): bool;

    /**
     * Delete FCM Token
     * @param int $customerId
     * @param string $fcmToken
     * @return bool
     */
    public function deleteToken($customerId, $fcmToken): bool;


    /**
     * Get all FCM tokens of current customer
     *
     * @return \Branch8\AppNotification\Api\Data\CustomerFcmTokenInterface[]
     */
    public function getCustomerTokens(): array;
}
