<?php

namespace Branch8\HotaiPoint\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class UsingCouponHistory extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('hotai_point_using_coupon_history', 'record_id');
    }
}
