<?php

namespace Branch8\HifiSalesReport\Model\HifiSalesReportRecord\Source;

use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;

class InvoiceStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('Invoice issued'), 'value' => HifiSalesReportRecord::INVOICE_STATUS_ISSUE],
            ['label' => __('Invoice canceled'), 'value' => HifiSalesReportRecord::INVOICE_STATUS_CANCEL],
            ['label' => __('Invoice allowances'), 'value' => HifiSalesReportRecord::INVOICE_STATUS_ALLOWANCES],
            ['label' => __('No invoice needed'), 'value' => HifiSalesReportRecord::INVOICE_STATUS_NO_INVOICE],
        ];
    }

    public static function getOptionArray()
    {
        return [
            HifiSalesReportRecord::INVOICE_STATUS_ISSUE      => __('Invoice issued'),
            HifiSalesReportRecord::INVOICE_STATUS_CANCEL     => __('Invoice canceled'),
            HifiSalesReportRecord::INVOICE_STATUS_ALLOWANCES => __('Invoice allowances'),
            HifiSalesReportRecord::INVOICE_STATUS_NO_INVOICE => __('No invoice needed')
        ];
    }
}