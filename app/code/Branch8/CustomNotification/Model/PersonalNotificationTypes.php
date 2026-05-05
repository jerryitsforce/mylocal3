<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       15/03/2026
 */

namespace Branch8\CustomNotification\Model;

use Magenest\NotificationBox\Model\Notification as NotificationModel;

class PersonalNotificationTypes
{
    private array $types;

    /**
     * @param array $types
     */
    public function __construct(
        array $types = []
    )
    {
        $this->types = $types;
    }

    /**
     * @return array
     */
    public function getList()
    {
        return array_merge($this->types, [
            NotificationModel::REVIEW_REMINDERS,
            NotificationModel::ORDER_STATUS_UPDATE,
            NotificationModel::ABANDONED_CART_REMINDS,
            'return_exchange',
            'spin_to_win'
        ]);
    }
}
