<?php

namespace Branch8\AppNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CustomerFcmToken extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_fcm_token_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('customer_fcm_token', 'entity_id');
        $this->_useIsObjectNew = true;
    }
}
