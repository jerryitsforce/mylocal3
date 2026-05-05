<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel;

use Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data\ProfileInterface;

class Profile extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('branch8_order_export_profile', 'entity_id');
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return void
     */
    public function beforeSave(\Magento\Framework\DataObject $object)
    {
        if (is_array($object->getData(ProfileInterface::ORDER_IDS))) {
            $object->setData(ProfileInterface::ORDER_IDS, implode(',',$object->getData(ProfileInterface::ORDER_IDS)));
        }
        parent::beforeSave($object);
    }
}
