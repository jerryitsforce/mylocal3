<?php
namespace Branch8\AppNotification\Api;

use Branch8\AppNotification\Api\Data\CustomerNotificationResponseInterface;

interface CustomerNotificationManagementInterface
{
    /**
     * Get customer notifications
     *
     * @param int $customerId
     * @param int $pageSize
     * @param int $curPage
     * @param int $type
     * @return \Branch8\AppNotification\Api\Data\CustomerNotificationResponseInterface
     */
    public function getList($customerId, $pageSize = 10, $curPage = 1, $type = 1);


    /**
     * @param int $customerId
     * @return int
     */
    public function getUnreadCount($customerId);

    /**
     * @param int $customerId
     * @param int $notificationId
     * @param string $entityIdType
     * @return \Branch8\AppNotification\Api\Data\MarkAsReadResponseInterface
     */
    public function markAsRead($customerId, $notificationId, $entityIdType);
}
