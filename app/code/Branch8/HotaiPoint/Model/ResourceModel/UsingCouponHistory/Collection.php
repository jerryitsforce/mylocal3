<?php

namespace Branch8\HotaiPoint\Model\ResourceModel\UsingCouponHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'hotai_point_using_coupon_history_collection_prefix';
    protected $_eventObject = 'hotai_point_using_coupon_history_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Branch8\HotaiPoint\Model\UsingCouponHistory',
            'Branch8\HotaiPoint\Model\ResourceModel\UsingCouponHistory'
        );
    }
}
