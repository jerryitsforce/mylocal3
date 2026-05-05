<?php

namespace Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken;

use Branch8\AppNotification\Model\CustomerFcmToken as Model;
use Branch8\AppNotification\Model\ResourceModel\CustomerFcmToken as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_fcm_token_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
