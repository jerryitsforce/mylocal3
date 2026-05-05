<?php

namespace Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketplaceParentOrderRetryCancel\Model\FailedRecord::class,
            \Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel\FailedRecord::class
        );
    }

}
