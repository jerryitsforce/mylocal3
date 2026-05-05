<?php

namespace Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord\Source;

use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord;

class SyncResult extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        return [
            ['label' => __('Yet synced'), 'value' => HifiSalesReportSubRecord::SYNC_RESULT_DEFAULT],
            ['label' => __('Sync success'), 'value' => HifiSalesReportSubRecord::SYNC_RESULT_SUCCESS],
            ['label' => __('Sync error'), 'value' => HifiSalesReportSubRecord::SYNC_RESULT_ERROR],
        ];
    }

    public static function getOptionArray()
    {
        return [
            HifiSalesReportSubRecord::SYNC_RESULT_DEFAULT    => __('Yet synced'),
            HifiSalesReportSubRecord::SYNC_RESULT_SUCCESS => __('Sync success'),
            HifiSalesReportSubRecord::SYNC_RESULT_ERROR => __('Sync error'),
        ];
    }
}