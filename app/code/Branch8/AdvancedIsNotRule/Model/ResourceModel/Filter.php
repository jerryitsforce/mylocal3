<?php
namespace Branch8\AdvancedIsNotRule\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Filter extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('branch8_salesrule_isnot_filter', 'filter_id');
    }
}
