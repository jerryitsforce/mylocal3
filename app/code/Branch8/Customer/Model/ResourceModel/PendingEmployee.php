<?php

namespace Branch8\Customer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PendingEmployee extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('customer_pending_employee', 'entity_id');
    }

    /**
     * Process before save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        // Set created_at if not set
        if (!$object->getCreatedAt()) {
            $object->setCreatedAt($this->_date->gmtDate());
        }

        // Always update updated_at
        $object->setUpdatedAt($this->_date->gmtDate());

        return parent::_beforeSave($object);
    }
}