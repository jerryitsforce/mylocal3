<?php

namespace Magenest\NotificationBox\Model;

use Magento\Framework\Model\AbstractModel;

class Notification extends AbstractModel
{
    protected $_eventPrefix = 'notification';

    const ACTIVE = 1;
    const NOT_ACTIVE = 0;
    const IS_SENT = 1;
    const IS_NOT_SENT = 0;
    const REVIEW_REMINDERS = "review_reminders";
    const REVIEW_REMINDERS_LABEL = "Review reminders";
    const ORDER_STATUS_UPDATE = "order_status_update";
    const ORDER_STATUS_UPDATE_LABEL = "Order status update";
    const ABANDONED_CART_REMINDS = "abandoned_cart_reminds";
    const ABANDONED_CART_REMINDS_LABEL = "Abandoned cart reminds";
    const CUSTOM_TYPE = "custom_notification_type";
    const CUSTOM_TYPE_LABEL = "Custom notification type";
    const CUSTOMER_NOT_LOGGER_IN = 0;
    const ALL_STORE_VIEWS = 0;

    protected function _construct()
    {
        $this->_init('Magenest\NotificationBox\Model\ResourceModel\Notification');
    }
}
