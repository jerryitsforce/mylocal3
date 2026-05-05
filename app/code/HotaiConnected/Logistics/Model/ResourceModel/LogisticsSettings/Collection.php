<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'hotaiconnected_logistics_settings_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'logistics_settings_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \HotaiConnected\Logistics\Model\LogisticsSettings::class,
            \HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings::class
        );
    }
}
