<?php

namespace Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'hifi_sales_report_record_collection_prefix';
    protected $_eventObject = 'hifi_sales_report_record_collection_object';

    /**
     * Define the resource model & the model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\HifiSalesReport\Model\HifiSalesReportRecord', 'Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord');
    }
}
