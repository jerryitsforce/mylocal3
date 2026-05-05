<?php

namespace Branch8\HifiSalesReport\Model\ResourceModel;

use Branch8\HifiSalesReport\Model\HifiSalesReportRecord as HifiSalesReportRecordModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HifiSalesReportRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(HifiSalesReportRecordModel::TABLE_NAME, HifiSalesReportRecordModel::ID_FIELD_NAME);
    }
}
