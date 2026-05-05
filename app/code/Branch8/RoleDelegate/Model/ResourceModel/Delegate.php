<?php
namespace Branch8\RoleDelegate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Delegate extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('branch8_role_delegate', 'id');
    }
}
