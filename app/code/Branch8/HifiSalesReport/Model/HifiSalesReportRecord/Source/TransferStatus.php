<?php

namespace Branch8\HifiSalesReport\Model\HifiSalesReportRecord\Source;

use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;

class TransferStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('Yet'), 'value' => HifiSalesReportRecord::TRANSFER_STATUS_YET],
            ['label' => __('Done'), 'value' => HifiSalesReportRecord::TRANSFER_STATUS_DONE],
        ];
    }

    public static function getOptionArray()
    {
        return [
            HifiSalesReportRecord::TRANSFER_STATUS_YET  => __('Yet'),
            HifiSalesReportRecord::TRANSFER_STATUS_DONE => __('Done'),
        ];
    }
}