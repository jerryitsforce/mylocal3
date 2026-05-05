<?php

namespace Branch8\HifiSalesReport\Model\ResourceModel;

use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as HifiSalesReportSubRecordModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HifiSalesReportSubRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(HifiSalesReportSubRecordModel::TABLE_NAME, HifiSalesReportSubRecordModel::ID_FIELD_NAME);
    }
}
