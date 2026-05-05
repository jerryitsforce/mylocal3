<?php

namespace Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord\Source;

use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord;

class InvoiceStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('Invoice issued'), 'value' => HifiSalesReportSubRecord::INVOICE_STATUS_ISSUE],
            ['label' => __('Invoice canceled'), 'value' => HifiSalesReportSubRecord::INVOICE_STATUS_CANCEL],
            ['label' => __('Invoice allowances'), 'value' => HifiSalesReportSubRecord::INVOICE_STATUS_ALLOWANCES],
            ['label' => __('No invoice needed'), 'value' => HifiSalesReportSubRecord::INVOICE_STATUS_NO_INVOICE],
        ];
    }

    public static function getOptionArray()
    {
        return [
            HifiSalesReportSubRecord::INVOICE_STATUS_ISSUE      => __('Invoice issued'),
            HifiSalesReportSubRecord::INVOICE_STATUS_CANCEL     => __('Invoice canceled'),
            HifiSalesReportSubRecord::INVOICE_STATUS_ALLOWANCES => __('Invoice allowances'),
            HifiSalesReportSubRecord::INVOICE_STATUS_NO_INVOICE => __('No invoice needed')
        ];
    }
}