<?php
namespace Branch8\RoleDelegate\Model\ResourceModel\Delegate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\Branch8\RoleDelegate\Model\Delegate::class, \Branch8\RoleDelegate\Model\ResourceModel\Delegate::class);
    }
}
