<?php

namespace Branch8\Customer\Model\ResourceModel\PendingEmployee;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\Customer\Model\PendingEmployee::class,
            \Branch8\Customer\Model\ResourceModel\PendingEmployee::class
        );
    }
}