<?php
namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProfileNotification extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('branch8_order_notification_profile', 'entity_id');
    }
}
