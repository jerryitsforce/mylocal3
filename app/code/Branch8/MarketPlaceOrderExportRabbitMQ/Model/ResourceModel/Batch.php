<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel;

use Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data\ProfileInterface;

class Batch extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('branch8_order_export_profile_batches', 'batch_id');
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return void
     */
    public function beforeSave(\Magento\Framework\DataObject $object)
    {
        if (is_array($object->getData('order_ids'))) {
            $object->setData('order_ids', implode(',', $object->getData('order_ids')));
        }
        parent::beforeSave($object);
    }
}
