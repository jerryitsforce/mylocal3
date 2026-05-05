<?php

namespace Branch8\CustomNotification\Controller\Adminhtml\Ajax;

use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\Notification as NotificationModel;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;

class PostProcess
{
    private DateTime $dateTime;
    private Json $serialize;

    /**
     * @param DateTime $dateTime
     * @param Json $serialize
     */
    public function __construct(
        DateTime $dateTime,
        Json     $serialize,
    )
    {
        $this->dateTime = $dateTime;
        $this->serialize = $serialize;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function execute($data)
    {
        if (isset($data['total_click'])) {
            unset($data['total_click']);
        }
        if (isset($data['total_sent'])) {
            unset($data['total_sent']);
        }
        if (isset($data['update_at'])) {
            unset($data['update_at']);
        }
        if (isset($data['created_at'])) {
            unset($data['created_at']);
        }
        if (isset($data['notification_type'])) {
            if (($data['notification_type'] == NotificationModel::REVIEW_REMINDERS ||
                    $data['notification_type'] == NotificationModel::ORDER_STATUS_UPDATE) &&
                $data['send_time'] == 'schedule_time') {
                $data['send_time'] = 'send_immediately';
            }
            if ($data['notification_type'] == NotificationModel::ABANDONED_CART_REMINDS && ($data['send_time'] == 'schedule_time' || $data['send_time'] == 'send_after_the_trigger_condition')) {
                $data['send_time'] = 'send_immediately';
            }
        }
        if (isset($data['store_view'])) {
            $data['store_view'] = $this->serialize->serialize($data['store_view']);
        }
        if (isset($data['customer_group'])) {
            $data['customer_group'] = $this->serialize->serialize($data['customer_group']);
        }
        // Save custom field
        if (isset($data['customer_levels']) && $data['customer_levels']) {
            $data['customer_levels'] = $this->serialize->serialize($data['customer_levels']);
        } else {
            $data['customer_levels'] = $this->serialize->serialize(\Magento\Customer\Model\Group::CUST_GROUP_ALL); // ALL
        }
        if ($data['notification_type'] == NotificationModel::REVIEW_REMINDERS) {
            $data['condition'] = $this->serialize->serialize($data['order_status_review']);
        } elseif ($data['notification_type'] == NotificationModel::ORDER_STATUS_UPDATE) {
            $data['condition'] = $this->serialize->serialize($data['order_status']);
        } elseif ($data['notification_type'] == NotificationModel::ABANDONED_CART_REMINDS) {
            $data['condition'] = $data['set_abandoned_cart_time'];
        } elseif ($data['notification_type'] == NotificationModel::CUSTOM_TYPE) {
            $data['condition'] = $data['custom_notification_type'];
        }
        if (isset($data['send_time'])) {
            if ($data['send_time'] == "schedule_time") {
                $data['schedule'] = $data['schedule_to'];
            }
            if ($data['send_time'] == "send_after_the_trigger_condition") {
                $chedule = ['send_after' => $data['send_after'], 'unit' => $data['unit']];
                $data['schedule'] = $this->serialize->serialize($chedule);
            }
        }
        unset($data['total_click']);
        unset($data['impression']);
        $data['is_sent'] = NotificationModel::IS_NOT_SENT;
        return $data;
    }
}
