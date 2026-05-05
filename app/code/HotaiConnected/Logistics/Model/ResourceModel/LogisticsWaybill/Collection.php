<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Logistics Waybill Collection
 */
class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \HotaiConnected\Logistics\Model\LogisticsWaybill::class,
            \HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill::class
        );
    }
}
